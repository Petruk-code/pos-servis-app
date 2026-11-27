<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketPart;
use App\Models\ServiceTicketStatusHistory;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CustomerServiceTicketStatusNotification;
use App\Notifications\ServiceTicketStatusNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceTicketController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ServiceTicket::class);

        $validated = $request->validate([
            'status' => ['nullable', 'in:antre,dicek,menunggu_sparepart,selesai,diambil,batal'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $validated['status'] ?? null;
        $search = trim($validated['search'] ?? '');

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $tickets = ServiceTicket::with(['technician', 'receiver', 'parts'])
            ->when(auth()->user()->role?->name === 'teknisi', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('technician_id')
                        ->orWhere('technician_id', auth()->id());
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search, $likeOperator) {
                $q->where(function ($sub) use ($search, $likeOperator) {
                    $sub->where('ticket_number', $likeOperator, "%{$search}%")
                        ->orWhere('customer_name', $likeOperator, "%{$search}%")
                        ->orWhere('customer_phone', $likeOperator, "%{$search}%")
                        ->orWhere('device_brand_model', $likeOperator, "%{$search}%")
                        ->orWhere('serial_number', $likeOperator, "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('services.index', compact('tickets', 'status', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', ServiceTicket::class);

        $technicians = User::whereHas('role', fn($q) => $q->whereIn('name', ['teknisi', 'owner']))
            ->where('is_active', true)
            ->get();

        return view('services.create', compact('technicians'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ServiceTicket::class);

        $validated = $request->validate([
            'customer_name'      => ['required', 'string', 'max:150'],
            'customer_phone'     => ['required', 'string', 'max:25'],
            'customer_email'     => ['nullable', 'email', 'max:150'],
            'notify_customer'    => ['nullable', 'boolean'],
            'device_type'        => ['required', 'string', 'max:100'],
            'device_brand_model' => ['required', 'string', 'max:150'],
            'serial_number'      => ['nullable', 'string', 'max:100'],
            'device_password'    => ['nullable', 'string', 'max:100'],
            'accessories'        => ['nullable', 'string'],
            'intake_checklist'   => ['nullable', 'array'],
            'intake_checklist.*' => ['string', 'max:100'],
            'intake_photo'       => ['nullable', 'image', 'max:4096'],
            'received_signature_name' => ['nullable', 'string', 'max:150'],
            'problem_description' => ['required', 'string'],
            'estimated_cost'     => ['nullable', 'numeric', 'min:0'],
            'estimated_finish_at' => ['nullable', 'date'],
            'technician_id'      => ['nullable', 'exists:users,id'],
        ]);
        $validated['notify_customer'] = $request->boolean('notify_customer') && !empty($validated['customer_email']);
        $validated['intake_checklist'] = array_values($request->input('intake_checklist', []));
        if ($request->hasFile('intake_photo')) {
            $validated['intake_photo_path'] = $request->file('intake_photo')->store('service-tickets/intake', 'public');
        }
        unset($validated['intake_photo']);

        $ticket = DB::transaction(function () use ($validated) {
            $today = now()->format('Ymd');

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $lastTicket = ServiceTicket::query()
                    ->whereDate('created_at', today())
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                $nextSequence = 1;
                if ($lastTicket && preg_match('/(\d+)$/', $lastTicket->ticket_number, $matches)) {
                    $nextSequence = ((int) $matches[1]) + 1;
                }

                $ticketNumber = 'SRV-' . $today . '-' . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);

                try {
                    $ticket = ServiceTicket::create([
                        ...$validated,
                        'ticket_number'  => $ticketNumber,
                        'estimated_cost' => $validated['estimated_cost'] ?? 0,
                        'received_by'    => auth()->id(),
                        'status'         => 'antre',
                        'payment_status' => 'unpaid',
                    ]);

                    ServiceTicketStatusHistory::create([
                        'service_ticket_id' => $ticket->id,
                        'changed_by' => auth()->id(),
                        'from_status' => null,
                        'to_status' => 'antre',
                        'notes' => 'Tiket dibuat',
                    ]);

                    return $ticket;
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23505') {
                        throw $exception;
                    }
                }
            }

            throw new \RuntimeException('Gagal membuat nomor tiket unik. Silakan coba lagi.');
        });

        $this->notifyServiceStatus($ticket, 'baru', 'antre', 'Tiket servis dibuat');

        return redirect()
            ->route('services.show', $ticket)
            ->with('success', "Tiket servis {$ticket->ticket_number} berhasil dibuat.");
    }

    public function show(ServiceTicket $service): View
    {
        $this->authorize('view', $service);

        $service->load(['technician', 'receiver', 'parts.product', 'transaction', 'statusHistories.user', 'warrantyClaims.reporter']);

        $customerHistory = ServiceTicket::with('parts')
            ->where('id', '!=', $service->id)
            ->where(function ($q) use ($service) {
                $q->where('customer_phone', $service->customer_phone);
                if ($service->serial_number) $q->orWhere('serial_number', $service->serial_number);
            })
            ->latest()->limit(10)->get();

        $products = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->where('sku', '!=', 'SRV-JASA')
            ->orderBy('name')
            ->get();

        $technicians = User::whereHas('role', fn($q) => $q->whereIn('name', ['teknisi', 'owner']))
            ->where('is_active', true)
            ->get();

        return view('services.show', [
            'ticket'      => $service,
            'products'    => $products,
            'technicians' => $technicians,
            'customerHistory' => $customerHistory,
        ]);
    }

    public function update(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'status'           => ['required', 'in:antre,dicek,menunggu_sparepart,selesai,diambil,batal'],
            'technician_id'    => ['nullable', 'exists:users,id'],
            'technician_notes' => ['nullable', 'string'],
            'service_fee'      => ['required', 'numeric', 'min:0'],
            'estimated_finish_at' => ['nullable', 'date'],
            'warranty_days'    => ['required', 'integer', 'min:0'],
            'customer_email'   => ['nullable', 'email', 'max:150'],
            'notify_customer'  => ['nullable', 'boolean'],
            'intake_checklist' => ['nullable', 'array'],
            'intake_checklist.*' => ['string', 'max:100'],
            'intake_photo'     => ['nullable', 'image', 'max:4096'],
            'handover_photo'   => ['nullable', 'image', 'max:4096'],
            'received_signature_name' => ['nullable', 'string', 'max:150'],
            'pickup_signature_name' => ['nullable', 'string', 'max:150'],
        ]);
        $validated['notify_customer'] = $request->boolean('notify_customer') && !empty($validated['customer_email'] ?? $service->customer_email);
        $validated['intake_checklist'] = array_values($request->input('intake_checklist', $service->intake_checklist ?? []));
        if ($request->hasFile('intake_photo')) {
            $validated['intake_photo_path'] = $request->file('intake_photo')->store('service-tickets/intake', 'public');
        }
        if ($request->hasFile('handover_photo')) {
            $validated['handover_photo_path'] = $request->file('handover_photo')->store('service-tickets/handover', 'public');
        }
        unset($validated['intake_photo'], $validated['handover_photo']);

        if ($validated['status'] === 'selesai' && !$service->completed_at) {
            $validated['completed_at'] = now();
        }
        if ($validated['status'] === 'diambil' && !$service->picked_up_at) {
            $validated['picked_up_at'] = now();
        }
        if (in_array($validated['status'], ['diambil', 'batal'], true)) {
            $validated['device_password'] = null;
        }

        $fromStatus = $service->status;
        $service->update($validated);

        if ($fromStatus !== $service->status) {
            ServiceTicketStatusHistory::create([
                'service_ticket_id' => $service->id,
                'changed_by' => auth()->id(),
                'from_status' => $fromStatus,
                'to_status' => $service->status,
                'notes' => 'Status diperbarui dari halaman detail servis',
            ]);
            $this->notifyServiceStatus($service, $fromStatus, $service->status, 'Status servis diperbarui');
        }

        return back()->with('success', 'Status & rincian servis berhasil diperbarui.');
    }

    public function addPart(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('manageParts', $service);

        if ($service->transaction_id && $service->payment_status === 'paid') {
            return back()->with('error', 'Tiket sudah lunas, sparepart tidak dapat diubah.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $service) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $qty = (int) $validated['quantity'];

            if ($product->stock < $qty) {
                throw ValidationException::withMessages([
                    'product_id' => "Stok {$product->name} tidak mencukupi (Tersisa: {$product->stock}).",
                ]);
            }

            $unitPrice = (float) $product->selling_price;
            $subtotal = $unitPrice * $qty;

            $existingPart = $service->parts()->where('product_id', $product->id)->first();
            if ($existingPart) {
                $existingPart->update([
                    'quantity' => $existingPart->quantity + $qty,
                    'subtotal' => ($existingPart->quantity + $qty) * $unitPrice,
                ]);
            } else {
                $service->parts()->create([
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                ]);
            }

            $stockBefore = $product->stock;
            $stockAfter = $stockBefore - $qty;
            $product->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id'    => $product->id,
                'user_id'       => auth()->id(),
                'movement_type' => 'outgoing',
                'quantity'      => -$qty,
                'stock_before'  => $stockBefore,
                'stock_after'   => $stockAfter,
                'notes'         => "Pemakaian sparepart servis - {$service->ticket_number}",
            ]);
        });

        return back()->with('success', 'Sparepart ditambahkan dan stok otomatis berkurang.');
    }

    public function removePart(ServiceTicket $service, ServiceTicketPart $part): RedirectResponse
    {
        $this->authorize('manageParts', $service);

        if ($service->transaction_id && $service->payment_status === 'paid') {
            return back()->with('error', 'Tiket sudah lunas, sparepart tidak dapat dihapus.');
        }

        DB::transaction(function () use ($service, $part) {
            $lockedPart = $service->parts()->lockForUpdate()->findOrFail($part->id);
            $product = Product::lockForUpdate()->findOrFail($lockedPart->product_id);
            $stockBefore = $product->stock;
            $stockAfter = $stockBefore + $lockedPart->quantity;

            $product->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id'    => $product->id,
                'user_id'       => auth()->id(),
                'movement_type' => 'incoming',
                'quantity'      => $lockedPart->quantity,
                'stock_before'  => $stockBefore,
                'stock_after'   => $stockAfter,
                'notes'         => "Batal pakai sparepart servis - {$service->ticket_number}",
            ]);

            $lockedPart->delete();
        });

        return back()->with('success', 'Sparepart dihapus dan stok dikembalikan ke gudang.');
    }

    public function printReceipt(ServiceTicket $service): View
    {
        $this->authorize('print', $service);

        $service->load(['technician', 'receiver', 'parts.product', 'transaction']);
        return view('services.print', ['ticket' => $service]);
    }

    public function pay(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('pay', $service);

        $service->load('parts.product', 'transaction');
        $grandTotal = (int) round($service->grand_total);
        $alreadyPaid = (int) round((float) $service->paid_amount);
        $remaining = max(0, $grandTotal - $alreadyPaid);

        if ($service->payment_status === 'paid') {
            return back()->with('error', 'Tiket servis ini sudah lunas.');
        }

        if ($grandTotal <= 0) {
            return back()->with('error', 'Total tagihan Rp 0. Isi Biaya Jasa atau Sparepart terlebih dahulu.');
        }

        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer,qris,debit,other'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $paidNow = (int) round((float) $validated['paid_amount']);
        $isFull = $paidNow >= $remaining;
        $recordPaid = $isFull ? $remaining : $paidNow;
        $changeAmount = $isFull ? ($paidNow - $remaining) : 0;
        $newPaid = $alreadyPaid + $recordPaid;
        $dueAmount = max(0, $grandTotal - $newPaid);
        $paymentStatus = $dueAmount <= 0 ? 'paid' : 'partial';
        $fromStatus = $service->status;

        DB::transaction(function () use ($service, $validated, $grandTotal, $recordPaid, $changeAmount, $dueAmount, $paymentStatus, $newPaid, $fromStatus) {
            $invoiceNumber = 'TRX-SRV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id'        => auth()->id(),
                'total'          => $grandTotal,
                'payment_method' => $validated['payment_method'],
                'paid_amount'    => $recordPaid,
                'change_amount'  => $changeAmount,
                'due_amount'     => $dueAmount,
                'status'         => 'completed',
                'payment_status' => $paymentStatus,
                'payment_notes'  => $validated['payment_notes'] ?? null,
            ]);

            if ((float) $service->service_fee > 0 && !$service->transaction_id) {
                $category = Category::firstOrCreate(['name' => 'Jasa Servis']);
                $jasaProduct = Product::firstOrCreate(
                    ['sku' => 'SRV-JASA'],
                    [
                        'category_id'   => $category->id,
                        'name'          => 'Jasa Servis Komputer',
                        'unit'          => 'jasa',
                        'cost_price'    => 0,
                        'selling_price' => 0,
                        'stock'         => 999999,
                        'minimum_stock' => 0,
                        'is_active'     => false,
                    ]
                );
                $transaction->details()->create([
                    'product_id'   => $jasaProduct->id,
                    'product_name' => "Jasa Servis ({$service->ticket_number} - {$service->device_brand_model})",
                    'sku'          => $jasaProduct->sku,
                    'quantity'     => 1,
                    'unit_price'   => (int) round((float) $service->service_fee),
                    'subtotal'     => (int) round((float) $service->service_fee),
                ]);
            }
            if (!$service->transaction_id) {
                foreach ($service->parts as $part) {
                    $transaction->details()->create([
                        'product_id'   => $part->product_id,
                        'product_name' => $part->product->name . " ({$service->ticket_number})",
                        'sku'          => $part->product->sku,
                        'quantity'     => $part->quantity,
                        'unit_price'   => (int) round((float) $part->unit_price),
                        'subtotal'     => (int) round((float) $part->subtotal),
                    ]);
                }
            } else {
                $transaction->details()->create([
                    'product_id'   => null,
                    'product_name' => "Pelunasan Servis ({$service->ticket_number})",
                    'sku'          => 'SRV-PAY',
                    'quantity'     => 1,
                    'unit_price'   => $recordPaid,
                    'subtotal'     => $recordPaid,
                ]);
            }

            $updates = [
                'transaction_id' => $transaction->id,
                'payment_status' => $paymentStatus,
                'paid_amount'    => $newPaid,
                'due_amount'     => $dueAmount,
            ];

            if ($paymentStatus === 'paid') {
                $updates['status'] = 'diambil';
                $updates['completed_at'] = $service->completed_at ?? now();
                $updates['picked_up_at'] = now();
                $updates['device_password'] = null;
            }

            $service->update($updates);

            $statusNote = $paymentStatus === 'paid' ? 'lunas' : 'DP/parsial';
            ServiceTicketStatusHistory::create([
                'service_ticket_id' => $service->id,
                'changed_by' => auth()->id(),
                'from_status' => $fromStatus,
                'to_status' => $service->status,
                'notes' => "Pembayaran servis {$statusNote} ({$transaction->invoice_number}), dibayar Rp " . number_format($recordPaid, 0, ',', '.'),
            ]);
        });

        $service->refresh();
        $msg = $service->payment_status === 'paid'
            ? 'Pembayaran servis lunas! Transaksi tercatat di Riwayat & Laporan.'
            : 'DP/parsial berhasil dicatat. Sisa tagihan: Rp ' . number_format($service->due_amount, 0, ',', '.');
        $this->notifyServiceStatus($service, $fromStatus ?? $service->status, $service->status, $msg);

        return redirect()->route('services.show', $service)->with('success', $msg);
    }

    public function storeWarrantyClaim(Request $request, ServiceTicket $service): RedirectResponse
    {
        $this->authorize('update', $service);
        $validated = $request->validate([
            'claim_date' => ['required', 'date'],
            'issue_description' => ['required', 'string', 'max:2000'],
        ]);
        $service->warrantyClaims()->create([
            ...$validated,
            'reported_by' => auth()->id(),
            'status' => 'open',
        ]);
        return back()->with('success', 'Klaim garansi dicatat.');
    }

    public function updateWarrantyClaim(Request $request, ServiceTicket $service, \App\Models\ServiceWarrantyClaim $claim): RedirectResponse
    {
        $this->authorize('update', $service);
        abort_unless($claim->service_ticket_id === $service->id, 404);
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,rejected'],
            'resolution' => ['nullable', 'string', 'max:2000'],
        ]);
        $claim->update([
            ...$validated,
            'resolved_at' => in_array($validated['status'], ['resolved','rejected'], true) ? now() : null,
        ]);
        return back()->with('success', 'Klaim garansi diperbarui.');
    }

    private function notifyServiceStatus(ServiceTicket $ticket, string $fromStatus, string $toStatus, ?string $note = null): void
    {
        $ticket->loadMissing(['receiver', 'technician']);
        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($ticket) {
                $q->whereHas('role', fn($r) => $r->whereIn('name', ['owner', 'kasir']))
                    ->orWhere('id', $ticket->received_by)
                    ->orWhere('id', $ticket->technician_id);
            })
            ->get()
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new ServiceTicketStatusNotification($ticket, $fromStatus, $toStatus, $note));
        }

        $notifiableStatuses = ['dicek', 'menunggu_sparepart', 'selesai', 'diambil', 'batal'];
        if ($ticket->notify_customer && $ticket->customer_email && in_array($toStatus, $notifiableStatuses, true)) {
            Notification::route('mail', $ticket->customer_email)
                ->notify(new CustomerServiceTicketStatusNotification($ticket, $fromStatus, $toStatus, $note));
        }
    }
}
