<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use Carbon\Carbon;
use Database\Factories\PaymentFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'recorded_by',
        'payment_date',
        'amount',
        'method',
        'cash_tendered',
        'change_amount',
        'reference',
        'notes',
        'status',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'voided_at' => 'datetime',
            'amount' => 'decimal:2',
            'cash_tendered' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentRecordStatus::class,
        ];
    }

    /**
     * Proteksi integritas finansial tingkat model:
     * 1. Pembayaran tidak boleh diedit atribut pentingnya secara diam-diam.
     * 2. Pembayaran tidak boleh dihapus secara fisik (anti-silent delete).
     */
    protected static function booted(): void
    {
        static::updating(function (Payment $payment) {
            $immutableAttributes = [
                'order_id',
                'amount',
                'method',
                'cash_tendered',
                'change_amount',
                'recorded_by',
                'payment_date',
            ];

            foreach ($immutableAttributes as $attr) {
                if ($payment->isDirty($attr)) {
                    throw new DomainException("Atribut '{$attr}' pada transaksi pembayaran bersifat permanen dan tidak boleh diubah secara langsung.");
                }
            }

            // Izinkan hanya transisi dari status Valid ke Voided
            if ($payment->isDirty('status')) {
                $originalStatus = $payment->getOriginal('status');
                $originalStatusValue = $originalStatus instanceof PaymentRecordStatus ? $originalStatus->value : (string) $originalStatus;
                $newStatusValue = $payment->status instanceof PaymentRecordStatus ? $payment->status->value : (string) $payment->status;

                if ($originalStatusValue !== PaymentRecordStatus::Valid->value || $newStatusValue !== PaymentRecordStatus::Voided->value) {
                    throw new DomainException("Status pembayaran hanya dapat diubah dari Valid menjadi Dibatalkan (Void).");
                }
            }
        });

        static::deleting(function (Payment $payment) {
            throw new DomainException("Data pembayaran historis tidak boleh dihapus dari sistem. Gunakan mekanisme void untuk pembatalan.");
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', PaymentRecordStatus::Valid->value);
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', PaymentRecordStatus::Voided->value);
    }

    public function scopeReceivedBetween(Builder $query, Carbon|string $from, Carbon|string $to): Builder
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    public function isValid(): bool
    {
        return $this->status === PaymentRecordStatus::Valid;
    }

    public function isVoided(): bool
    {
        return $this->status === PaymentRecordStatus::Voided;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp' . number_format((float) $this->amount, 0, ',', '.');
    }

    public function getFormattedCashTenderedAttribute(): ?string
    {
        return $this->cash_tendered !== null
            ? 'Rp' . number_format((float) $this->cash_tendered, 0, ',', '.')
            : null;
    }

    public function getFormattedChangeAmountAttribute(): ?string
    {
        return $this->change_amount !== null
            ? 'Rp' . number_format((float) $this->change_amount, 0, ',', '.')
            : null;
    }
}
