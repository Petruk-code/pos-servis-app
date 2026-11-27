<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketStatusHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAuditAndPolicyTest extends TestCase
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

    public function test_service_status_history_is_recorded_on_create_update_pay_and_cancel(): void
    {
        $kasir = $this->user('kasir');
        $owner = $this->user('owner');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock' => 5,
            'selling_price' => 100000,
            'cost_price' => 50000,
        ]);

        $ticket = ServiceTicket::factory()->create([
            'received_by' => $kasir->id,
            'status' => 'antre',
            'service_fee' => 50000,
        ]);
        ServiceTicketStatusHistory::create([
            'service_ticket_id' => $ticket->id,
            'changed_by' => $kasir->id,
            'from_status' => null,
            'to_status' => 'antre',
            'notes' => 'Tiket dibuat',
        ]);

        $this->actingAs($kasir)->put(route('services.update', $ticket), [
            'status' => 'dicek',
            'technician_id' => null,
            'technician_notes' => 'Cek awal',
            'service_fee' => 50000,
            'warranty_days' => 0,
        ])->assertRedirect();

        $this->actingAs($kasir)->post(route('services.parts.add', $ticket), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->actingAs($kasir)->post(route('services.pay', $ticket), [
            'paid_amount' => 150000,
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertDatabaseHas('service_ticket_status_histories', [
            'service_ticket_id' => $ticket->id,
            'from_status' => 'antre',
            'to_status' => 'dicek',
        ]);
        $this->assertDatabaseHas('service_ticket_status_histories', [
            'service_ticket_id' => $ticket->id,
            'to_status' => 'diambil',
        ]);

        $this->actingAs($owner)->post(route('transactions.cancel', $ticket->transaction_id), [
            'cancellation_reason' => 'Tes pembatalan servis',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_ticket_status_histories', [
            'service_ticket_id' => $ticket->id,
            'to_status' => 'selesai',
        ]);
    }

    public function test_kasir_cannot_view_other_kasir_transaction_but_owner_can(): void
    {
        $kasirA = $this->user('kasir');
        $kasirB = $this->user('kasir');
        $owner = $this->user('owner');

        $tx = Transaction::create([
            'invoice_number' => 'TRX-TEST-001',
            'user_id' => $kasirA->id,
            'total' => 10000,
            'payment_method' => 'cash',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($kasirB)->get(route('transactions.show', $tx))->assertForbidden();
        $this->actingAs($owner)->get(route('transactions.show', $tx))->assertOk();
        $this->actingAs($owner)->get(route('transactions.index'))->assertOk();
    }
}
