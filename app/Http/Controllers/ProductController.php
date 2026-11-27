<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');

        $products = Product::with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['stock'] = 0;

        $product = Product::create($validated);
        Audit::log('product.created', $product, [], $product->only(['sku','name','category_id','cost_price','selling_price','minimum_stock','is_active']), 'Membuat produk');

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $old = $product->only(['sku','name','category_id','cost_price','selling_price','minimum_stock','is_active']);
        $product->update($validated);
        Audit::log('product.updated', $product, $old, $product->only(['sku','name','category_id','cost_price','selling_price','minimum_stock','is_active']), 'Memperbarui produk');

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        if ($product->transactionDetails()->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'Produk tidak dapat dihapus karena sudah digunakan dalam transaksi.');
        }

        if ($product->stockMovements()->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'Produk tidak dapat dihapus karena memiliki riwayat pergerakan stok.');
        }

        $old = $product->only(['sku','name','category_id','cost_price','selling_price','minimum_stock','is_active']);
        $product->delete();
        Audit::log('product.deleted', null, $old, [], 'Menghapus produk: '.($old['sku'] ?? ''));

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
