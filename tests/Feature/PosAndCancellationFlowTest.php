<?php

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAndCancellationFlowTest extends TestCase
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

    public function test_pos_checkout_creates_transaction_details_and_stock_movements(): void
    {
        $kasir = $this->user('kasir');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock' => 10,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($kasir)->postJson(route('pos.checkout'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'paid_amount' => 60000,
        ]);

        $response->assertCreated()->assertJsonPath('change_amount', 10000);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $kasir->id,
            'total' => 50000,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('transaction_details', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 50000,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => -2,
            'stock_after' => 8,
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
    }

    public function test_kasir_can_request_and_owner_can_reject_cancellation(): void
    {
        $kasir = $this->user('kasir');
        $owner = $this->user('owner');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock' => 8,
            'selling_price' => 10000,
        ]);

        $tx = Transaction::create([
            'invoice_number' => 'TRX-REJ-001',
            'user_id' => $kasir->id,
            'total' => 20000,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);
        $tx->details()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $this->actingAs($kasir)
            ->post(route('transactions.request-cancellation', $tx), ['reason' => 'Input salah'])
            ->assertRedirect();

        $request = CancellationRequest::firstOrFail();
        $this->assertEquals('pending', $request->status);

        $this->actingAs($owner)
            ->post(route('cancellation-requests.reject', $request), ['review_notes' => 'Tidak valid'])
            ->assertRedirect();

        $this->assertDatabaseHas('cancellation_requests', ['id' => $request->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'completed']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
    }

    public function test_owner_can_approve_cancellation_and_stock_is_restored(): void
    {
        $kasir = $this->user('kasir');
        $owner = $this->user('owner');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock' => 8,
            'selling_price' => 10000,
        ]);

        $tx = Transaction::create([
            'invoice_number' => 'TRX-APR-001',
            'user_id' => $kasir->id,
            'total' => 20000,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);
        $tx->details()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $request = CancellationRequest::create([
            'transaction_id' => $tx->id,
            'requested_by' => $kasir->id,
            'reason' => 'Salah input',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('cancellation-requests.approve', $request), ['review_notes' => 'Disetujui'])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('cancellation_requests', ['id' => $request->id, 'status' => 'approved']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
        $this->assertDatabaseHas('stock_movements', [
            'transaction_id' => $tx->id,
            'product_id' => $product->id,
            'movement_type' => 'incoming',
            'quantity' => 2,
        ]);
    }
}
