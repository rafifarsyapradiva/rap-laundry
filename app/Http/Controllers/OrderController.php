<?php

namespace App\Http\Controllers;

use App\Enums\LaundryStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Requests\Order\CorrectOrderStatusRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\InvoiceService;
use App\Services\OrderService;
use DomainException;
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
            'payments.recorder',
            'payments.voider',
            'statusHistories.changer',
        ]);

        $paymentMethods = \App\Enums\PaymentMethod::cases();

        return view('orders.show', compact('order', 'paymentMethods'));
    }

    /**
     * Tampilkan halaman nota pesanan yang ramah cetak (A4 atau Struk Thermal).
     */
    public function print(
        Request $request,
        Order $order,
        InvoiceService $invoiceService
    ): View {
        Gate::authorize('view', $order);

        $defaultFormat = BusinessSetting::get(BusinessSetting::KEY_PRINT_FORMAT, 'a4');
        $format = $request->query('format', $defaultFormat);

        if (! in_array($format, ['thermal', 'a4'], true)) {
            $format = 'a4';
        }

        $autoPrint = $request->boolean('auto_print', false);
        $invoiceData = $invoiceService->getInvoiceData($order);

        return view('orders.print', array_merge($invoiceData, [
            'currentFormat' => $format,
            'autoPrint' => $autoPrint,
        ]));
    }

    /**
     * Perbarui status pengerjaan cucian atau batalkan pesanan secara terkontrol.
     */
    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderService $orderService
    ): RedirectResponse {
        $validated = $request->validated();
        $targetStatus = LaundryStatus::from($validated['status']);

        try {
            $orderService->updateLaundryStatus(
                order: $order,
                newStatus: $targetStatus,
                operator: $request->user(),
                note: $validated['note'] ?? null,
                cancellationReason: $validated['cancellation_reason'] ?? null
            );

            $message = $targetStatus === LaundryStatus::Dibatalkan
                ? "Pesanan {$order->order_number} berhasil dibatalkan."
                : "Status cucian pesanan {$order->order_number} berhasil diperbarui menjadi {$targetStatus->label()}.";

            return redirect()
                ->route('orders.show', $order)
                ->with('success', $message);
        } catch (InvalidStatusTransitionException|DomainException $e) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['status' => $e->getMessage()]);
        }
    }

    /**
     * Lakukan koreksi status cucian secara terkontrol khusus untuk Administrator.
     */
    public function correctStatus(
        CorrectOrderStatusRequest $request,
        Order $order,
        OrderService $orderService
    ): RedirectResponse {
        $validated = $request->validated();
        $targetStatus = LaundryStatus::from($validated['status']);

        try {
            $orderService->correctLaundryStatus(
                order: $order,
                newStatus: $targetStatus,
                admin: $request->user(),
                reason: $validated['reason']
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Status cucian pesanan {$order->order_number} berhasil dikoreksi menjadi {$targetStatus->label()}.");
        } catch (DomainException $e) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['correction' => $e->getMessage()]);
        }
    }
}


