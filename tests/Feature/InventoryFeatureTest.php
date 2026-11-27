<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    public function test_owner_can_create_supplier_and_kasir_cannot(): void
    {
        $owner = $this->createUser('owner');
        $kasir = $this->createUser('kasir');

        $this->actingAs($kasir)->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($owner)->post(route('suppliers.store'), [
            'name' => 'Supplier A',
            'contact_name' => 'Budi',
            'phone' => '08123',
            'email' => 's@example.com',
            'address' => 'Jl. A',
            'is_active' => 1,
        ])->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Supplier A']);
    }

    public function test_receiving_purchase_order_adds_stock_and_movement(): void
    {
        $owner = $this->createUser('owner');
        $supplier = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 2, 'cost_price' => 1000]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-1',
            'supplier_id' => $supplier->id,
            'user_id' => $owner->id,
            'order_date' => now()->toDateString(),
            'status' => 'draft',
            'total' => 5000,
        ]);
        $po->items()->create(['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 1200, 'subtotal' => 6000]);

        $this->actingAs($owner)->post(route('purchase-orders.receive', $po))->assertRedirect();
        $po->refresh();
        $product->refresh();
        $this->assertEquals('received', $po->status);
        $this->assertEquals(7, $product->stock);
        $this->assertEquals(1200.00, (float) $product->cost_price);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'incoming',
            'quantity' => 5,
            'stock_before' => 2,
            'stock_after' => 7,
        ]);
    }

    public function test_stock_opname_only_adjusts_changed_items(): void
    {
        $owner = $this->createUser('owner');
        $category = Category::factory()->create();
        $p1 = Product::factory()->create(['category_id' => $category->id, 'stock' => 10, 'is_active' => true]);
        $p2 = Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'is_active' => true]);

        $this->actingAs($owner)->post(route('stock-opname.store'), [
            'notes' => 'Opname test',
            'items' => [
                ['product_id' => $p1->id, 'counted_stock' => 8],
                ['product_id' => $p2->id, 'counted_stock' => 5],
            ],
        ])->assertRedirect();

        $p1->refresh(); $p2->refresh();
        $this->assertEquals(8, $p1->stock);
        $this->assertEquals(5, $p2->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p1->id, 'movement_type' => 'adjustment', 'quantity' => -2]);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $p2->id, 'movement_type' => 'adjustment', 'notes' => 'Stok opname: Opname test']);
    }
}
