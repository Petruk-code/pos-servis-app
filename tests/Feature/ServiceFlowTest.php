<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createRole(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['description' => ucfirst($name)]);
    }

    private function createUser(string $role): User
    {
        return User::factory()->create(['role_id' => $this->createRole($role)->id]);
    }

    public function test_kasir_can_pay_service_and_stock_movements_are_recorded(): void
    {
        $kasir = $this->createUser('kasir');
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'RAM 8GB',
            'stock' => 10,
            'selling_price' => 100000,
            'cost_price' => 80000,
        ]);

        $ticket = ServiceTicket::factory()->create([
            'received_by' => $kasir->id,
            'status' => 'selesai',
            'service_fee' => 50000,
        ]);

        $this->actingAs($kasir)
            ->post(route('services.parts.add', $ticket), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'outgoing',
            'quantity' => -1,
        ]);

        $ticket->refresh();
        $this->actingAs($kasir)
            ->post(route('services.pay', $ticket), [
                'paid_amount' => 150000,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('services.show', $ticket));

        $ticket->refresh();
        $this->assertNotNull($ticket->transaction_id);
        $this->assertEquals('diambil', $ticket->status);
        $this->assertDatabaseHas('transactions', [
            'id' => $ticket->transaction_id,
            'status' => 'completed',
            'total' => 150000,
        ]);
    }

    public function test_teknisi_cannot_view_other_technician_ticket(): void
    {
        $teknisiA = $this->createUser('teknisi');
        $teknisiB = $this->createUser('teknisi');
        $ticket = ServiceTicket::factory()->create([
            'received_by' => $teknisiA->id,
            'technician_id' => $teknisiB->id,
        ]);

        $this->actingAs($teknisiA)
            ->get(route('services.show', $ticket))
            ->assertForbidden();
    }

    public function test_cancelled_service_transaction_resets_ticket(): void
    {
        $owner = $this->createUser('owner');
        $kasir = $this->createUser('kasir');
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'selling_price' => 100000]);
        $ticket = ServiceTicket::factory()->create([
            'received_by' => $kasir->id,
            'status' => 'selesai',
            'service_fee' => 100000,
        ]);

        $this->actingAs($kasir)->post(route('services.parts.add', $ticket), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->actingAs($kasir)->post(route('services.pay', $ticket), ['paid_amount' => 200000, 'payment_method' => 'cash'])
            ->assertRedirect(route('services.show', $ticket));

        $ticket->refresh();
        $transactionId = $ticket->transaction_id;
        $this->assertNotNull($transactionId);

        $this->actingAs($owner)
            ->post(route('transactions.cancel', $transactionId), [
                'cancellation_reason' => 'Salah input pembayaran servis',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertNull($ticket->transaction_id);
        $this->assertEquals('selesai', $ticket->status);
        $this->assertNull($ticket->picked_up_at);
        $this->assertDatabaseHas('transactions', ['id' => $transactionId, 'status' => 'cancelled']);
    }
}
