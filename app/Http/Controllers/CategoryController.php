<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $categories = Category::when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $category = Category::create($validated);
        Audit::log('category.created', $category, [], $category->only(['name','description','is_active']), 'Membuat kategori');

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $old = $category->only(['name','description','is_active']);
        $category->update($validated);
        Audit::log('category.updated', $category, $old, $category->only(['name','description','is_active']), 'Memperbarui kategori');

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);
        $old = $category->only(['name','description','is_active']);
        $category->delete();
        Audit::log('category.deleted', null, $old, [], 'Menghapus kategori: '.($old['name'] ?? ''));

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
