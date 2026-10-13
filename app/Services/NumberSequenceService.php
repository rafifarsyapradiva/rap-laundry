<?php

namespace App\Services;

use App\Models\NumberSequence;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    /**
     * Dapatkan nomor urut berikutnya secara atomik dengan pessimistic lock (anti-race condition).
     */
    public function next(string $key, string $period = 'all'): int
    {
        $operation = function () use ($key, $period): int {
            // 1. Pastikan baris sequence sudah terdaftar tanpa risiko duplikasi
            NumberSequence::query()->insertOrIgnore([
                'sequence_key' => $key,
                'period' => $period,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Kunci baris nyata dengan pesimistik FOR UPDATE
            /** @var NumberSequence $sequence */
            $sequence = NumberSequence::query()
                ->where('sequence_key', $key)
                ->where('period', $period)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Tambahkan counter dan simpan
            $sequence->last_value += 1;
            $sequence->save();

            return (int) $sequence->last_value;
        };

        // Jika pemanggil sudah di dalam DB::transaction, jalankan langsung; jika belum, bungkus dalam transaksi
        if (DB::transactionLevel() > 0) {
            return $operation();
        }

        return DB::transaction($operation);
    }

    /**
     * Hasilkan kode pelanggan otomatis berikutnya (misal: CUST-0001, CUST-0002).
     */
    public function generateCustomerCode(): string
    {
        $sequenceValue = $this->next(key: 'customer', period: 'all');

        return sprintf('CUST-%04d', $sequenceValue);
    }

    /**
     * Hasilkan nomor transaksi unik berikutnya (misal: LD-20261013-0001).
     */
    public function generateOrderNumber(string $prefix = 'LD', ?DateTimeInterface $date = null): string
    {
        $period = Carbon::instance($date ?? now())->format('Ymd');
        $sequenceValue = $this->next(key: 'order', period: $period);

        return sprintf('%s-%s-%04d', strtoupper($prefix), $period, $sequenceValue);
    }
}
