<?php

namespace App\Services;

use App\Enums\BillingUnit;
use App\Enums\PaymentMethod;
use App\Models\BusinessSetting;
use App\Models\Order;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Susun dan transformasikan seluruh data pesanan, item snapshot,
     * transaksi pembayaran, dan identitas usaha menjadi representasi nota siap cetak.
     *
     * @return array<string, mixed>
     */
    public function getInvoiceData(Order $order): array
    {
        // Eager load seluruh relasi historis untuk eliminasi N+1 query loop
        $order->loadMissing([
            'customer',
            'items.service',
            'creator',
            'canceller',
            'validPayments.recorder',
            'statusHistories.changer',
        ]);

        // 1. Identitas Usaha dari Pengaturan Bisnis Lokal
        $businessInfo = [
            'name' => BusinessSetting::get(BusinessSetting::KEY_BUSINESS_NAME, 'RAP Laundry'),
            'address' => BusinessSetting::get(BusinessSetting::KEY_BUSINESS_ADDRESS, 'Jl. Kebon Jeruk No. 12, Jakarta Barat'),
            'phone' => BusinessSetting::get(BusinessSetting::KEY_BUSINESS_PHONE, '081234567890'),
            'operating_hours' => BusinessSetting::get(BusinessSetting::KEY_OPERATING_HOURS, 'Senin - Sabtu: 08.00 - 20.00 WIB'),
            'footer_note' => BusinessSetting::get(
                BusinessSetting::KEY_RECEIPT_FOOTER_NOTE,
                'Harap bawa nota ini saat pengambilan pakaian. Pakaian yang tidak diambil dalam 30 hari di luar tanggung jawab laundry.'
            ),
            'default_print_format' => BusinessSetting::get(BusinessSetting::KEY_PRINT_FORMAT, 'a4'),
        ];

        // 2. Data Pelanggan
        $customer = $order->customer;
        $customerInfo = [
            'id' => $customer?->id,
            'name' => $customer?->name ?? 'Pelanggan Umum',
            'customer_code' => $customer?->customer_code ?? '-',
            'phone' => $customer?->phone ?? '-',
            'address' => $customer?->address ?? '-',
        ];

        // 3. Format Rincian Item Layanan (Snapshot Data)
        $items = $order->items->map(function ($item) {
            $isDecimal = $item->billing_unit_snapshot === BillingUnit::Kg;
            $formattedQuantity = $isDecimal
                ? number_format((float) $item->quantity, 2, ',', '.')
                : number_format((float) $item->quantity, 0, ',', '.');

            return [
                'id' => $item->id,
                'service_name' => $item->service_name_snapshot,
                'billing_unit' => $item->billing_unit_snapshot->label(),
                'billing_unit_raw' => $item->billing_unit_snapshot->value,
                'quantity' => (float) $item->quantity,
                'quantity_formatted' => $formattedQuantity,
                'unit_price' => (float) $item->unit_price,
                'unit_price_formatted' => 'Rp' . number_format((float) $item->unit_price, 0, ',', '.'),
                'subtotal' => (float) $item->subtotal,
                'subtotal_formatted' => 'Rp' . number_format((float) $item->subtotal, 0, ',', '.'),
                'notes' => $item->notes,
            ];
        })->values()->all();

        // 4. Kalkulasi Finansial Sesuai Integritas Gate 8 & Gate 9
        $subtotal = (float) $order->subtotal;
        $discountAmount = (float) $order->discount_amount;
        $extraCharge = (float) $order->extra_charge;
        $totalAmount = (float) $order->total_amount;
        $totalPaid = (float) $order->total_paid;
        $remainingBalance = (float) $order->remaining_balance;
        $isPaid = $remainingBalance <= 0.0;

        $financials = [
            'subtotal' => $subtotal,
            'subtotal_formatted' => 'Rp' . number_format($subtotal, 0, ',', '.'),
            'discount_amount' => $discountAmount,
            'discount_amount_formatted' => 'Rp' . number_format($discountAmount, 0, ',', '.'),
            'has_discount' => $discountAmount > 0.0,
            'extra_charge' => $extraCharge,
            'extra_charge_formatted' => 'Rp' . number_format($extraCharge, 0, ',', '.'),
            'has_extra_charge' => $extraCharge > 0.0,
            'total_amount' => $totalAmount,
            'total_amount_formatted' => 'Rp' . number_format($totalAmount, 0, ',', '.'),
            'total_paid' => $totalPaid,
            'total_paid_formatted' => 'Rp' . number_format($totalPaid, 0, ',', '.'),
            'remaining_balance' => $remainingBalance,
            'remaining_balance_formatted' => 'Rp' . number_format($remainingBalance, 0, ',', '.'),
            'is_paid' => $isPaid,
            'payment_status_label' => $order->payment_status->label(),
            'laundry_status_label' => $order->laundry_status->label(),
        ];

        // 5. Riwayat Pembayaran Valid Terinci
        $payments = $order->validPayments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('d-m-Y H:i') : '-',
                'method' => $payment->method->label(),
                'method_raw' => $payment->method->value,
                'is_cash' => $payment->method === PaymentMethod::Tunai,
                'amount' => (float) $payment->amount,
                'amount_formatted' => 'Rp' . number_format((float) $payment->amount, 0, ',', '.'),
                'cash_tendered' => $payment->cash_tendered !== null ? (float) $payment->cash_tendered : null,
                'cash_tendered_formatted' => $payment->cash_tendered !== null ? 'Rp' . number_format((float) $payment->cash_tendered, 0, ',', '.') : null,
                'change_amount' => $payment->change_amount !== null ? (float) $payment->change_amount : null,
                'change_amount_formatted' => $payment->change_amount !== null ? 'Rp' . number_format((float) $payment->change_amount, 0, ',', '.') : null,
                'reference' => $payment->reference,
                'notes' => $payment->notes,
                'recorder_name' => $payment->recorder?->name ?? 'Kasir',
            ];
        })->values()->all();

        // Pembayaran tunai terakhir untuk ringkasan uang diterima & kembalian
        $lastCashPayment = $order->validPayments
            ->where('method', PaymentMethod::Tunai)
            ->last();

        $cashSummary = null;
        if ($lastCashPayment && $lastCashPayment->cash_tendered !== null) {
            $cashSummary = [
                'cash_tendered' => (float) $lastCashPayment->cash_tendered,
                'cash_tendered_formatted' => 'Rp' . number_format((float) $lastCashPayment->cash_tendered, 0, ',', '.'),
                'change_amount' => (float) $lastCashPayment->change_amount,
                'change_amount_formatted' => 'Rp' . number_format((float) $lastCashPayment->change_amount, 0, ',', '.'),
            ];
        }

        return [
            'order' => $order,
            'business' => $businessInfo,
            'customer' => $customerInfo,
            'items' => $items,
            'financials' => $financials,
            'payments' => $payments,
            'cash_summary' => $cashSummary,
            'timestamps' => [
                'received_at' => $order->received_at ? $order->received_at->format('d-m-Y H:i') . ' WIB' : '-',
                'estimated_completion_at' => $order->estimated_completion_at ? $order->estimated_completion_at->format('d-m-Y H:i') . ' WIB' : 'Sesuai antrean',
                'completed_at' => $order->completed_at ? $order->completed_at->format('d-m-Y H:i') . ' WIB' : '-',
                'cancelled_at' => $order->cancelled_at ? $order->cancelled_at->format('d-m-Y H:i') . ' WIB' : null,
                'printed_at' => Carbon::now()->format('d-m-Y H:i:s') . ' WIB',
            ],
            'operator' => [
                'name' => $order->creator?->name ?? 'Staf Operasional',
                'role' => $order->creator?->role?->label() ?? 'Kasir',
            ],
        ];
    }
}
