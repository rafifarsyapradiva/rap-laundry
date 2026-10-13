<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Http\Requests\Payment\VoidPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
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

            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Pembayaran #{$payment->id} ({$payment->formatted_amount}) berhasil dibatalkan (void).");
        } catch (DomainException $e) {
            return redirect()
                ->route('orders.show', $payment->order)
                ->withErrors(['void' => $e->getMessage()]);
        }
    }
}
