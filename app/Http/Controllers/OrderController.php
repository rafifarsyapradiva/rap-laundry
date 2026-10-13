<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Tampilkan formulir pembuatan pesanan laundry baru.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Order::class);

        // Ambil data pelanggan aktif
        $customers = Customer::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'customer_code', 'name', 'phone', 'address']);

        // Ambil data katalog layanan aktif
        $services = Service::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'billing_unit', 'price', 'estimated_hours']);

        // Konfigurasi estimasi waktu dan diskon usaha
        $defaultEstimatedHours = (int) BusinessSetting::get(BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS, '48');
        $maxDiscountPercent = (float) BusinessSetting::get(BusinessSetting::KEY_CASHIER_MAX_DISCOUNT_PERCENT, '20');

        $receivedAt = now();
        $defaultEstimatedCompletionAt = now()->addHours($defaultEstimatedHours);

        // Pelanggan terpilih (jika dialihkan dengan query param ?customer_id=...)
        $selectedCustomerId = $request->query('customer_id');

        return view('orders.create', compact(
            'customers',
            'services',
            'defaultEstimatedHours',
            'maxDiscountPercent',
            'receivedAt',
            'defaultEstimatedCompletionAt',
            'selectedCustomerId'
        ));
    }

    /**
     * Simpan transaksi pesanan baru.
     * Logika persistensi dan kalkulasi database menyeluruh akan dilengkapi pada Task 6.2.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        // Validasi form StoreOrderRequest telah berhasil diverifikasi
        // Pada Task 6.2, OrderService::createOrder() akan dipanggil di sini.
        return redirect()
            ->route('orders.create')
            ->with('success', 'Form pesanan valid dan siap diproses ke database transaksi.');
    }
}
