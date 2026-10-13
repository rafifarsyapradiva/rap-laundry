<?php

namespace App\Services;

use App\Enums\LaundryStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class DashboardService
{
    /**
     * Ambil seluruh metrik ringkasan operasional dan finansial dashboard harian.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $today = Carbon::today();

        // 1. Hitung jumlah pesanan masuk hari ini
        $ordersTodayCount = Order::query()
            ->whereDate('received_at', $today)
            ->count();

        // 2. Hitung pesanan yang sedang diproses (Menunggu Proses, Dicuci, Dikeringkan, Disetrika)
        $processingOrdersCount = Order::query()
            ->whereIn('laundry_status', [
                LaundryStatus::MenungguProses->value,
                LaundryStatus::Dicuci->value,
                LaundryStatus::Dikeringkan->value,
                LaundryStatus::Disetrika->value,
            ])
            ->count();

        // 3. Hitung cucian siap diambil
        $readyOrdersCount = Order::query()
            ->where('laundry_status', LaundryStatus::SiapDiambil->value)
            ->count();

        // 4. Hitung pesanan yang sudah diambil (selesai)
        $completedOrdersCount = Order::query()
            ->where('laundry_status', LaundryStatus::Selesai->value)
            ->count();

        // Pesanan yang diambil hari ini
        $completedTodayCount = Order::query()
            ->where('laundry_status', LaundryStatus::Selesai->value)
            ->whereDate('completed_at', $today)
            ->count();

        // 5. Hitung pembayaran valid yang diterima hari ini (kas masuk riil)
        $cashInflowToday = (float) Payment::query()
            ->valid()
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // 6. Hitung total saldo tagihan belum lunas (piutang usaha aktif)
        $unpaidOrdersTotal = (float) Order::query()
            ->where('laundry_status', '!=', LaundryStatus::Dibatalkan->value)
            ->where('payment_status', '!=', PaymentStatus::Lunas->value)
            ->sum('total_amount');

        $unpaidOrdersPaid = (float) Payment::query()
            ->valid()
            ->whereHas('order', function ($query) {
                $query->where('laundry_status', '!=', LaundryStatus::Dibatalkan->value)
                    ->where('payment_status', '!=', PaymentStatus::Lunas->value);
            })
            ->sum('amount');

        $totalUnpaidBalance = max(0.0, $unpaidOrdersTotal - $unpaidOrdersPaid);

        return [
            'orders_today_count' => $ordersTodayCount,
            'processing_orders_count' => $processingOrdersCount,
            'ready_orders_count' => $readyOrdersCount,
            'completed_orders_count' => $completedOrdersCount,
            'completed_today_count' => $completedTodayCount,
            'cash_inflow_today' => $cashInflowToday,
            'total_unpaid_balance' => $totalUnpaidBalance,
        ];
    }

    /**
     * Ambil daftar 10 transaksi pesanan terbaru dengan relasi ter-eager load.
     *
     * @param int $limit
     * @return Collection<int, Order>
     */
    public function getRecentOrders(int $limit = 10): Collection
    {
        return Order::query()
            ->with(['customer', 'creator', 'validPayments'])
            ->latest('received_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Siapkan data tren 7 hari terakhir untuk visualisasi grafik offline murni (SVG/HTML).
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getWeeklyTrend(): SupportCollection
    {
        $days = collect();
        $startDate = Carbon::today()->subDays(6);

        // Ambil data agregasi pesanan 7 hari terakhir
        $dailyOrders = Order::query()
            ->selectRaw('DATE(received_at) as date, COUNT(*) as count')
            ->where('received_at', '>=', $startDate->copy()->startOfDay())
            ->groupBy('date')
            ->pluck('count', 'date');

        // Ambil data agregasi pembayaran valid 7 hari terakhir
        $dailyPayments = Payment::query()
            ->valid()
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->where('payment_date', '>=', $startDate->copy()->startOfDay())
            ->groupBy('date')
            ->pluck('total', 'date');

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateKey = $currentDate->format('Y-m-d');

            $orderCount = (int) ($dailyOrders[$dateKey] ?? 0);
            $cashAmount = (float) ($dailyPayments[$dateKey] ?? 0.0);

            $days->push([
                'date' => $dateKey,
                'label' => $currentDate->isoFormat('ddd, D MMM'),
                'short_label' => $currentDate->isoFormat('D/M'),
                'is_today' => $currentDate->isToday(),
                'orders_count' => $orderCount,
                'cash_amount' => $cashAmount,
                'formatted_cash' => 'Rp' . number_format($cashAmount, 0, ',', '.'),
            ]);
        }

        return $days;
    }

    /**
     * Dapatkan ringkasan distribusi status cucian untuk status badge ringkasan.
     *
     * @return array<string, int>
     */
    public function getStatusDistribution(): array
    {
        $counts = Order::query()
            ->selectRaw('laundry_status, COUNT(*) as total')
            ->groupBy('laundry_status')
            ->pluck('total', 'laundry_status')
            ->toArray();

        $distribution = [];
        foreach (LaundryStatus::cases() as $status) {
            $distribution[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $distribution;
    }
}
