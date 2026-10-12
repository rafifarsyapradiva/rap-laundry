<?php

use App\Http\Controllers\Auth\AuthController;
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
