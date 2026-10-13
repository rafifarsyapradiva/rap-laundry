<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\OrderService;
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
     * Simpan transaksi pesanan baru ke dalam database secara atomik.
     */
    public function store(StoreOrderRequest $request, OrderService $orderService): RedirectResponse
    {
        $order = $orderService->createOrder(
            data: $request->validated(),
            operator: $request->user()
        );

        $targetRoute = \Illuminate\Support\Facades\Route::has('orders.show')
            ? route('orders.show', $order)
            : route('orders.create');

        return redirect($targetRoute)
            ->with('success', "Pesanan {$order->order_number} berhasil dibuat dan disimpan.");
    }

    /**
     * Tampilkan halaman rincian detail transaksi pesanan.
     */
    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        // Eager load relasi penting untuk eliminasi N+1 query loop
        $order->load([
            'customer',
            'items',
            'creator',
            'canceller',
            'validPayments.recorder',
            'statusHistories.changer',
        ]);

        return view('orders.show', compact('order'));
    }
}
