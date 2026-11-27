<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', StockMovement::class);
        $search = trim((string) $request->query('search', ''));
        $products = Product::query()
            ->where('is_active', true)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();
        return view('stock-opname.create', compact('products', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.counted_stock' => ['required', 'integer', 'min:0'],
        ]);

        $changes = 0;
        DB::transaction(function () use ($validated, &$changes) {
            foreach ($validated['items'] as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $before = (int) $product->stock;
                $after = (int) $item['counted_stock'];
                if ($before === $after) continue;
                $delta = $after - $before;
                $product->update(['stock' => $after]);
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'transaction_id' => null,
                    'movement_type' => 'adjustment',
                    'quantity' => $delta,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'notes' => trim('Stok opname' . (($validated['notes'] ?? '') !== '' ? ': ' . $validated['notes'] : '')),
                ]);
                Audit::log('stock_opname.adjusted', $movement, ['stock' => $before], ['stock' => $after, 'product_id' => $product->id, 'delta' => $delta], 'Penyesuaian stok opname');
                $changes++;
            }
        });

        return redirect()->route('stock-opname.create', ['search' => $request->query('search')])
            ->with('success', $changes > 0 ? "Stok opname disimpan. {$changes} produk disesuaikan." : 'Tidak ada perubahan stok.');
    }
}
