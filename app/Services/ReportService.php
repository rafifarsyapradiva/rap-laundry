<?php

namespace App\Services;

use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Sanitasi sel data teks untuk mencegah kerentanan CSV Formula Injection (CWE-1236).
     */
    public static function sanitizeCsv(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $str)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Ambil metrik finansial dan operasional utama untuk periode tanggal tertentu.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array<string, mixed>
     */
    public function getSummaryMetrics(Carbon $start, Carbon $end): array
    {
        // 1. Total jumlah pesanan masuk pada rentang tanggal received_at
        $ordersCount = Order::query()
            ->whereBetween('received_at', [$start, $end])
            ->count();

        // 2. Pesanan yang dibatalkan
        $cancelledOrdersCount = Order::query()
            ->whereBetween('received_at', [$start, $end])
            ->where('laundry_status', LaundryStatus::Dibatalkan->value)
            ->count();

        // 3. Total nilai transaksi (gross revenue / omzet pesanan masuk)
        $grossRevenue = (float) Order::query()
            ->whereBetween('received_at', [$start, $end])
            ->where('laundry_status', '!=', LaundryStatus::Dibatalkan->value)
            ->sum('total_amount');

        // 4. Total kas masuk riil (pembayaran valid berdasarkan payment_date)
        $cashInflow = (float) Payment::query()
            ->valid()
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');

        // 5. Total sisa tagihan/piutang dari pesanan yang masuk pada rentang tanggal ini
        $activeOrderIds = Order::query()
            ->whereBetween('received_at', [$start, $end])
            ->where('laundry_status', '!=', LaundryStatus::Dibatalkan->value)
            ->where('payment_status', '!=', PaymentStatus::Lunas->value)
            ->pluck('id');

        $activeOrdersTotal = (float) Order::query()
            ->whereIn('id', $activeOrderIds)
            ->sum('total_amount');

        $activeOrdersPaid = (float) Payment::query()
            ->valid()
            ->whereIn('order_id', $activeOrderIds)
            ->sum('amount');

        $outstandingBalance = max(0.0, $activeOrdersTotal - $activeOrdersPaid);

        return [
            'orders_count' => $ordersCount,
            'active_orders_count' => max(0, $ordersCount - $cancelledOrdersCount),
            'cancelled_orders_count' => $cancelledOrdersCount,
            'gross_revenue' => $grossRevenue,
            'cash_inflow' => $cashInflow,
            'outstanding_balance' => $outstandingBalance,
        ];
    }

    /**
     * Laporan penerimaan kas berdasarkan metode pembayaran (hanya pembayaran valid pada payment_date).
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getPaymentMethodBreakdown(Carbon $start, Carbon $end): SupportCollection
    {
        $raw = Payment::query()
            ->valid()
            ->whereBetween('payment_date', [$start, $end])
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get()
            ->keyBy(fn ($item) => $item->method instanceof PaymentMethod ? $item->method->value : (string) $item->method);

        $totalCash = (float) Payment::query()
            ->valid()
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');

        $breakdown = collect();

        foreach (PaymentMethod::cases() as $method) {
            $record = $raw->get($method->value);
            $amount = $record ? (float) $record->total : 0.0;
            $count = $record ? (int) $record->count : 0;
            $percentage = $totalCash > 0 ? round(($amount / $totalCash) * 100, 1) : 0.0;

            $breakdown->push([
                'method' => $method,
                'method_value' => $method->value,
                'method_label' => $method->label(),
                'transaction_count' => $count,
                'total_amount' => $amount,
                'formatted_total' => 'Rp' . number_format($amount, 0, ',', '.'),
                'percentage' => $percentage,
            ]);
        }

        return $breakdown;
    }

    /**
     * Laporan layanan yang paling sering dipesan dalam rentang tanggal received_at.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int $limit
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getTopServices(Carbon $start, Carbon $end, int $limit = 10): SupportCollection
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.received_at', [$start, $end])
            ->where('orders.laundry_status', '!=', LaundryStatus::Dibatalkan->value)
            ->selectRaw('
                order_items.service_name_snapshot as service_name,
                order_items.billing_unit_snapshot as billing_unit,
                COUNT(DISTINCT orders.id) as order_frequency,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.subtotal) as total_subtotal
            ')
            ->groupBy('order_items.service_name_snapshot', 'order_items.billing_unit_snapshot')
            ->orderByDesc('total_subtotal')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'service_name' => $item->service_name,
                    'billing_unit' => $item->billing_unit,
                    'order_frequency' => (int) $item->order_frequency,
                    'total_quantity' => (float) $item->total_quantity,
                    'total_subtotal' => (float) $item->total_subtotal,
                    'formatted_subtotal' => 'Rp' . number_format((float) $item->total_subtotal, 0, ',', '.'),
                ];
            });
    }

    /**
     * Laporan distribusi status cucian pesanan dalam rentang tanggal.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array<string, int>
     */
    public function getOrdersByLaundryStatus(Carbon $start, Carbon $end): array
    {
        $counts = Order::query()
            ->whereBetween('received_at', [$start, $end])
            ->selectRaw('laundry_status, COUNT(*) as count')
            ->groupBy('laundry_status')
            ->pluck('count', 'laundry_status')
            ->toArray();

        $distribution = [];
        foreach (LaundryStatus::cases() as $status) {
            $distribution[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $distribution;
    }

    /**
     * Laporan distribusi status pembayaran pesanan dalam rentang tanggal.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array<string, int>
     */
    public function getOrdersByPaymentStatus(Carbon $start, Carbon $end): array
    {
        $counts = Order::query()
            ->whereBetween('received_at', [$start, $end])
            ->selectRaw('payment_status, COUNT(*) as count')
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();

        $distribution = [];
        foreach (PaymentStatus::cases() as $pStatus) {
            $distribution[$pStatus->value] = (int) ($counts[$pStatus->value] ?? 0);
        }

        return $distribution;
    }

    /**
     * Ambil rincian pesanan untuk tabel laporan dan ekspor.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection<int, Order>
     */
    public function getDetailedOrders(Carbon $start, Carbon $end): Collection
    {
        return Order::query()
            ->with(['customer', 'creator', 'validPayments'])
            ->whereBetween('received_at', [$start, $end])
            ->orderBy('received_at', 'asc')
            ->get();
    }

    /**
     * Ambil rincian pembayaran valid untuk tabel arus kas masuk dan ekspor.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection<int, Payment>
     */
    public function getDetailedPayments(Carbon $start, Carbon $end): Collection
    {
        return Payment::query()
            ->valid()
            ->with(['order.customer', 'recorder'])
            ->whereBetween('payment_date', [$start, $end])
            ->orderBy('payment_date', 'asc')
            ->get();
    }

    /**
     * Ekspor CSV Riwayat Transaksi Pesanan secara aman (dengan UTF-8 BOM & sanitasi formula).
     */
    public function exportOrdersCsv(Carbon $start, Carbon $end): StreamedResponse
    {
        $filename = sprintf(
            'Laporan-Transaksi-Pesanan-%s-%s.csv',
            $start->format('Ymd'),
            $end->format('Ymd')
        );

        $orders = $this->getDetailedOrders($start, $end);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            // Tulis UTF-8 BOM agar aksen dan karakter Rupiah terbaca mulus di Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header kolom CSV
            fputcsv($handle, [
                'Nomor Nota',
                'Tanggal Masuk',
                'Kode Pelanggan',
                'Nama Pelanggan',
                'Telepon Pelanggan',
                'Status Cucian',
                'Status Pembayaran',
                'Subtotal (Rp)',
                'Diskon (Rp)',
                'Biaya Tambahan (Rp)',
                'Total Tagihan (Rp)',
                'Total Dibayar (Rp)',
                'Sisa Tagihan (Rp)',
                'Petugas Kasir',
                'Catatan',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    self::sanitizeCsv($order->order_number),
                    self::sanitizeCsv($order->received_at->format('d/m/Y H:i')),
                    self::sanitizeCsv($order->customer?->customer_code ?? ''),
                    self::sanitizeCsv($order->customer?->name ?? '-'),
                    self::sanitizeCsv($order->customer?->phone ?? ''),
                    self::sanitizeCsv($order->laundry_status->label()),
                    self::sanitizeCsv($order->payment_status->label()),
                    (float) $order->subtotal,
                    (float) $order->discount_amount,
                    (float) $order->extra_charge,
                    (float) $order->total_amount,
                    (float) $order->total_paid,
                    (float) $order->remaining_balance,
                    self::sanitizeCsv($order->creator?->name ?? '-'),
                    self::sanitizeCsv($order->notes ?? ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Ekspor CSV Buku Kas Masuk Penerimaan Pembayaran secara aman.
     */
    public function exportPaymentsCsv(Carbon $start, Carbon $end): StreamedResponse
    {
        $filename = sprintf(
            'Laporan-Kas-Masuk-%s-%s.csv',
            $start->format('Ymd'),
            $end->format('Ymd')
        );

        $payments = $this->getDetailedPayments($start, $end);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($payments) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header kolom
            fputcsv($handle, [
                'ID Pembayaran',
                'Tanggal Pembayaran',
                'Nomor Nota Pesanan',
                'Pelanggan',
                'Metode Pembayaran',
                'Nominal Masuk (Rp)',
                'Uang Diterima Tunai (Rp)',
                'Uang Kembalian (Rp)',
                'Referensi / Bank / QRIS',
                'Petugas Kasir',
                'Catatan Pembayaran',
            ]);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    self::sanitizeCsv($payment->id),
                    self::sanitizeCsv($payment->payment_date->format('d/m/Y H:i')),
                    self::sanitizeCsv($payment->order?->order_number ?? '-'),
                    self::sanitizeCsv($payment->order?->customer?->name ?? '-'),
                    self::sanitizeCsv($payment->method->label()),
                    (float) $payment->amount,
                    $payment->cash_tendered !== null ? (float) $payment->cash_tendered : '',
                    $payment->change_amount !== null ? (float) $payment->change_amount : '',
                    self::sanitizeCsv($payment->reference ?? ''),
                    self::sanitizeCsv($payment->recorder?->name ?? '-'),
                    self::sanitizeCsv($payment->notes ?? ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
