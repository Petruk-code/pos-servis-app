<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $status = $request->query('status');
        $orders = PurchaseOrder::with(['supplier', 'user'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()->paginate(15)->withQueryString();
        return view('purchase-orders.index', compact('orders', 'status'));
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);
        return view('purchase-orders.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);
        $data = $this->validated($request);
        $po = DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => 'PO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'total' => 0,
            ]);
            $total = 0;
            foreach ($data['items'] as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['unit_cost'];
                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }
            $po->update(['total' => $total]);
            Audit::log('purchase_order.created', $po, [], ['po_number' => $po->po_number, 'total' => $total, 'status' => 'draft'], 'Membuat PO');
            return $po;
        });
        return redirect()->route('purchase-orders.show', $po)->with('success', 'Purchase Order dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['supplier', 'user', 'items.product']);
        return view('purchase-orders.show', ['po' => $purchaseOrder]);
    }

    public function receive(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('receive', $purchaseOrder);
        DB::transaction(function () use ($purchaseOrder) {
            $po = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if ($po->status !== 'draft') abort(409, 'PO sudah diproses.');
            $po->load('items');
            foreach ($po->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $before = (int) $product->stock;
                $after = $before + (int) $item->quantity;
                $product->update(['stock' => $after, 'cost_price' => $item->unit_cost]);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'transaction_id' => null,
                    'movement_type' => 'incoming',
                    'quantity' => (int) $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'notes' => 'Penerimaan PO ' . $po->po_number,
                ]);
            }
            $po->update(['status' => 'received', 'received_date' => now()->toDateString()]);
            Audit::log('purchase_order.received', $po, ['status' => 'draft'], ['status' => 'received'], 'Menerima PO');
        });
        return back()->with('success', 'PO diterima. Stok diperbarui.');
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);
        $purchaseOrder->update(['status' => 'cancelled']);
        Audit::log('purchase_order.cancelled', $purchaseOrder, ['status' => 'draft'], ['status' => 'cancelled'], 'Membatalkan PO');
        return back()->with('success', 'PO dibatalkan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
