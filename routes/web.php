<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CancellationRequestController;
use App\Http\Controllers\ServiceTicketController;

// Halaman utama diarahkan ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Halaman setelah login
Route::middleware('auth')->group(function () {

    // Dashboard untuk semua role yang sudah login
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Khusus Owner
    Route::middleware('role:owner')->group(function () {

        Route::get('/owner', [OwnerController::class, 'index'])
            ->name('owner');

        // Manajemen kategori
        Route::resource('categories', CategoryController::class)
            ->except(['show']);

        // Manajemen produk
        Route::resource('products', ProductController::class)
            ->except(['show']);

        // Manajemen karyawan
        Route::resource('employees', EmployeeController::class)
            ->except(['show']);

        // Manajemen laporan
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])
            ->name('reports.export');

        // Pembatalan transaksi langsung oleh Owner
        Route::post(
            '/transactions/{transaction}/cancel',
            [TransactionController::class, 'cancel']
        )->name('transactions.cancel');

        // Daftar pengajuan pembatalan transaksi
        Route::get(
            '/cancellation-requests',
            [CancellationRequestController::class, 'index']
        )->name('cancellation-requests.index');

        // Menyetujui pengajuan pembatalan
        Route::post(
            '/cancellation-requests/{cancellationRequest}/approve',
            [CancellationRequestController::class, 'approve']
        )->name('cancellation-requests.approve');

        // Menolak pengajuan pembatalan
        Route::post(
            '/cancellation-requests/{cancellationRequest}/reject',
            [CancellationRequestController::class, 'reject']
        )->name('cancellation-requests.reject');
    });

    // Kasir & Owner (POS, transaksi)
    Route::middleware('role:owner,kasir')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])
            ->name('transactions.index');

        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
            ->name('transactions.show');
    });

    // Khusus Kasir
    Route::middleware('role:kasir')->group(function () {

        Route::get('/pos', [PosController::class, 'index'])
            ->name('pos');

        Route::post('/pos/checkout', [PosController::class, 'checkout'])
            ->name('pos.checkout');

        // Pengajuan pembatalan oleh Kasir
        Route::post(
            '/transactions/{transaction}/request-cancellation',
            [TransactionController::class, 'requestCancellation']
        )->name('transactions.request-cancellation');
    });

    // Khusus Gudang
    Route::middleware('role:gudang,owner')->group(function () {

        Route::get('/inventory', function () {
            return 'Halaman Inventaris Gudang';
        })->name('inventory');

        Route::get('/stock-movements', [StockMovementController::class, 'index'])
            ->name('stock-movements.index');

        Route::get('/stock-movements/create', [StockMovementController::class, 'create'])
            ->name('stock-movements.create');

        Route::post('/stock-movements', [StockMovementController::class, 'store'])
            ->name('stock-movements.store');
    });

    // Modul Servis (Owner, Kasir, Teknisi)
    Route::middleware('role:owner,kasir,teknisi')->group(function () {
        Route::get('/services', [ServiceTicketController::class, 'index'])->name('services.index');
        Route::get('/services/create', [ServiceTicketController::class, 'create'])->name('services.create');
        Route::post('/services', [ServiceTicketController::class, 'store'])->name('services.store');
        Route::get('/services/{service}', [ServiceTicketController::class, 'show'])->name('services.show');
        Route::put('/services/{service}', [ServiceTicketController::class, 'update'])->name('services.update');
        Route::post('/services/{service}/parts', [ServiceTicketController::class, 'addPart'])->name('services.parts.add');
        Route::delete('/services/{service}/parts/{part}', [ServiceTicketController::class, 'removePart'])->name('services.parts.remove');
        Route::get('/services/{service}/print', [ServiceTicketController::class, 'printReceipt'])->name('services.print');
        Route::get('/transactions/{transaction}/print', [TransactionController::class, 'printReceipt'])->name('transactions.print');
    });

    // Pembayaran Servis ke Kasir (Owner & Kasir)
    Route::middleware('role:owner,kasir')->group(function () {
        Route::post('/services/{service}/pay', [ServiceTicketController::class, 'pay'])->name('services.pay');
        Route::post('/services/{service}/warranty-claims', [ServiceTicketController::class, 'storeWarrantyClaim'])->name('services.warranty-claims.store');
        Route::patch('/services/{service}/warranty-claims/{claim}', [ServiceTicketController::class, 'updateWarrantyClaim'])->name('services.warranty-claims.update');
    });

    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.read-all');

    Route::post('/notifications/{id}/read', function (string $id) {
        $n = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();
        return redirect($n->data['url'] ?? back()->getTargetUrl());
    })->name('notifications.read');
});
