<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);
        $search = trim((string) $request->query('search', ''));
        $suppliers = Supplier::query()
            ->when($search !== '', fn($q) => $q->where('name','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"))
            ->latest()->paginate(15)->withQueryString();
        return view('suppliers.index', compact('suppliers','search'));
    }

    public function create(): View { $this->authorize('create', Supplier::class); return view('suppliers.create'); }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'contact_name'=>['nullable','string','max:255'],
            'phone'=>['nullable','string','max:30'],
            'email'=>['nullable','email','max:255'],
            'address'=>['nullable','string','max:1000'],
            'is_active'=>['required','boolean'],
        ]);
        Supplier::create($data);
        return redirect()->route('suppliers.index')->with('success','Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View { $this->authorize('update', $supplier); return view('suppliers.edit', compact('supplier')); }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'contact_name'=>['nullable','string','max:255'],
            'phone'=>['nullable','string','max:30'],
            'email'=>['nullable','email','max:255'],
            'address'=>['nullable','string','max:1000'],
            'is_active'=>['required','boolean'],
        ]);
        $supplier->update($data);
        return redirect()->route('suppliers.index')->with('success','Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);
        if ($supplier->products()->exists()) return back()->with('error','Supplier tidak bisa dihapus karena masih dipakai produk.');
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success','Supplier berhasil dihapus.');
    }
}
