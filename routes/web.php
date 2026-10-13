<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — LaundryDesk (RAP Laundry)
|--------------------------------------------------------------------------
*/

// Pengalihan halaman utama ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rute khusus tamu (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

// Rute terautentikasi (wajib login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Rute operasional harian (dapat diakses Kasir dan Admin)
    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard.index');
        })->name('dashboard');

        // Modul Transaksi Pembuatan Pesanan Laundry
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::patch('/orders/{order}/correct-status', [OrderController::class, 'correctStatus'])->name('orders.correct-status');

        // Modul Transaksi Pembayaran Manual
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
        Route::patch('/payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');

        // Modul Pengelolaan Data Pelanggan
        Route::resource('customers', CustomerController::class);
        Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
        Route::patch('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::delete('/customers/{customer}/force-delete', [CustomerController::class, 'forceDelete'])->name('customers.force-delete');

        // Modul Master Layanan dan Tarif
        Route::resource('services', ServiceController::class);
        Route::post('/services/{service}/restore', [ServiceController::class, 'restore'])->name('services.restore');
        Route::patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
        Route::delete('/services/{service}/force-delete', [ServiceController::class, 'forceDelete'])->name('services.force-delete');
    });

    // Rute administratif khusus pemilik / Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', function () {
            return view('admin.users.index');
        })->name('users.index');

        Route::get('/settings', function () {
            return view('admin.settings.index');
        })->name('settings.index');

        Route::get('/audit-logs', function () {
            $logs = \App\Models\ActivityLog::with('user')->latest()->paginate(15);
            return view('admin.audit-logs.index', compact('logs'));
        })->name('audit-logs.index');

        Route::get('/reports/export', function () {
            return view('admin.reports.export');
        })->name('reports.export');
    });
});
