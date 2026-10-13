<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Http\Requests\Payment\VoidPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Tampilkan riwayat dan daftar seluruh transaksi pembayaran dengan filter & metrik ringkasan.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $query = Payment::query()
            ->with(['order.customer', 'recorder', 'voider']);

        // 1. Pencarian multi-kriteria (nomor nota, nama pelanggan, telepon, nomor referensi, catatan, ID)
        $searchTerm = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));
        if ($searchTerm !== '') {
            $query->where(function (Builder $sub) use ($searchTerm) {
                $sub->where('reference', 'like', "%{$searchTerm}%")
                    ->orWhere('notes', 'like', "%{$searchTerm}%")
                    ->orWhere('id', $searchTerm)
                    ->orWhereHas('order', function (Builder $orderSub) use ($searchTerm) {
                        $orderSub->where('order_number', 'like', "%{$searchTerm}%")
                            ->orWhereHas('customer', function (Builder $custSub) use ($searchTerm) {
                                $custSub->where('name', 'like', "%{$searchTerm}%")
                                    ->orWhere('phone', 'like', "%{$searchTerm}%");
                            });
                    });
            });
        }

        // 2. Filter rentang tanggal pembayaran
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        // 3. Filter kanal/metode pembayaran
        if ($method = $request->input('method')) {
            if (PaymentMethod::tryFrom($method)) {
                $query->where('method', $method);
            }
        }

        // 4. Filter status pencatatan pembayaran (Valid / Voided)
        if ($status = $request->input('status')) {
            if (PaymentRecordStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        // 5. Filter petugas kasir pencatat
        if ($userId = $request->input('user_id')) {
            $query->where('recorded_by', $userId);
        }

        // Hitung metrik ringkasan finansial (sesuai query filter aktif)
        $metricsQuery = clone $query;
        $totalValidAmount = (clone $metricsQuery)->where('status', PaymentRecordStatus::Valid->value)->sum('amount');
        $cashValidAmount = (clone $metricsQuery)->where('status', PaymentRecordStatus::Valid->value)->where('method', PaymentMethod::Tunai->value)->sum('amount');
        $nonCashValidAmount = (clone $metricsQuery)->where('status', PaymentRecordStatus::Valid->value)->where('method', '!=', PaymentMethod::Tunai->value)->sum('amount');
        $voidedCount = (clone $metricsQuery)->where('status', PaymentRecordStatus::Voided->value)->count();

        $payments = $query->latest('payment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $methods = PaymentMethod::cases();
        $recordStatuses = PaymentRecordStatus::cases();
        $users = User::orderBy('name')->get();

        return view('payments.index', [
            'payments' => $payments,
            'methods' => $methods,
            'recordStatuses' => $recordStatuses,
            'users' => $users,
            'metrics' => [
                'total_valid' => (float) $totalValidAmount,
                'cash_valid' => (float) $cashValidAmount,
                'non_cash_valid' => (float) $nonCashValidAmount,
                'voided_count' => $voidedCount,
            ],
        ]);
    }

    /**
     * Catat transaksi pembayaran baru untuk pesanan tertentu.
     */
    public function store(
        RecordPaymentRequest $request,
        Order $order,
        PaymentService $paymentService
    ): RedirectResponse {
        try {
            $payment = $paymentService->recordPayment(
                order: $order,
                data: $request->validated(),
                operator: $request->user()
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Pembayaran sebesar {$payment->formatted_amount} berhasil dicatat.");
        } catch (DomainException $e) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['payment' => $e->getMessage()]);
        }
    }

    /**
     * Batalkan (void) pembayaran yang telah tercatat dengan alasan audit.
     */
    public function void(
        VoidPaymentRequest $request,
        Payment $payment,
        PaymentService $paymentService
    ): RedirectResponse {
        try {
            $order = $payment->order;

            $paymentService->voidPayment(
                payment: $payment,
                admin: $request->user(),
                reason: $request->validated('reason')
            );

            $targetRoute = $request->input('redirect_to') === 'payments'
                ? route('payments.index')
                : route('orders.show', $order);

            return redirect($targetRoute)
                ->with('success', "Pembayaran #{$payment->id} ({$payment->formatted_amount}) berhasil dibatalkan (void).");
        } catch (DomainException $e) {
            $targetRoute = $request->input('redirect_to') === 'payments'
                ? route('payments.index')
                : route('orders.show', $payment->order);

            return redirect($targetRoute)
                ->withErrors(['void' => $e->getMessage()]);
        }
    }
}
