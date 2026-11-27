<?php

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['description' => ucfirst($name)]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => $this->role($role)->id]);
    }

    public function test_service_cannot_be_paid_when_already_paid(): void
    {
        $kasir = $this->user('kasir');
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'selling_price' => 100000, 'cost_price' => 50000]);
        $ticket = ServiceTicket::factory()->create([
            'received_by' => $kasir->id,
            'status' => 'selesai',
            'service_fee' => 50000,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'due_amount' => 150000,
        ]);
        $ticket->parts()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100000, 'unit_cost' => 50000, 'subtotal' => 100000]);

        $this->actingAs($kasir)->post(route('services.pay', $ticket), [
            'paid_amount' => 150000, 'payment_method' => 'cash',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('paid', $ticket->payment_status);

        $resp = $this->actingAs($kasir)->post(route('services.pay', $ticket), [
            'paid_amount' => 150000, 'payment_method' => 'cash',
        ]);
        // Already paid: controller returns error redirect, or policy may forbid
        $this->assertTrue(in_array($resp->status(), [302, 403]));
        $this->assertEquals(1, Transaction::where('invoice_number', 'like', 'TRX-SRV-%')->count());
    }

    public function test_pos_checkout_rejects_insufficient_cash_and_bad_stock(): void
    {
        $kasir = $this->user('kasir');
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 1, 'selling_price' => 100000, 'is_active' => true]);

        $this->actingAs($kasir)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 50000,
            'payment_method' => 'cash',
        ])->assertStatus(422);

        $this->actingAs($kasir)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'paid_amount' => 300000,
            'payment_method' => 'cash',
        ])->assertStatus(422);

        $product->refresh();
        $this->assertEquals(1, $product->stock);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_completed_transaction_cannot_be_cancelled_twice(): void
    {
        $owner = $this->user('owner');
        $kasir = $this->user('kasir');
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'selling_price' => 10000, 'cost_price' => 5000]);

        $this->actingAs($kasir)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'paid_amount' => 20000,
            'payment_method' => 'cash',
        ])->assertSuccessful();

        $transaction = Transaction::firstOrFail();
        $this->actingAs($owner)->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Test cancel',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(5, $product->stock);
        $transaction->refresh();
        $this->assertEquals('cancelled', $transaction->status);

        $resp = $this->actingAs($owner)->post(route('transactions.cancel', $transaction), [
            'cancellation_reason' => 'Cancel lagi',
        ]);
        $this->assertTrue(in_array($resp->status(), [302, 403, 422]));
        $product->refresh();
        $this->assertEquals(5, $product->stock);
    }

    public function test_duplicate_pending_cancellation_request_is_rejected(): void
    {
        $owner = $this->user('owner');
        $kasir = $this->user('kasir');
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'selling_price' => 10000]);

        $this->actingAs($kasir)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 10000,
            'payment_method' => 'cash',
        ])->assertSuccessful();
        $transaction = Transaction::firstOrFail();

        $this->actingAs($kasir)->post(route('transactions.request-cancellation', $transaction), [
            'reason' => 'Alasan pertama cukup panjang',
        ])->assertRedirect();

        $resp = $this->actingAs($kasir)->post(route('transactions.request-cancellation', $transaction), [
            'reason' => 'Alasan kedua cukup panjang',
        ]);
        $resp->assertSessionHasErrors();
        $this->assertEquals(1, CancellationRequest::where('transaction_id', $transaction->id)->where('status', 'pending')->count());
    }
}
