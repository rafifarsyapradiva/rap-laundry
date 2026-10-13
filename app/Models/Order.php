<?php

namespace App\Models;

use App\Enums\LaundryStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'created_by',
        'received_at',
        'estimated_completion_at',
        'completed_at',
        'subtotal',
        'discount_amount',
        'extra_charge',
        'total_amount',
        'notes',
        'laundry_status',
        'payment_status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'estimated_completion_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'extra_charge' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'laundry_status' => LaundryStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function validPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', PaymentRecordStatus::Valid->value);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function latestStatusHistory(): HasOne
    {
        return $this->hasOne(OrderStatusHistory::class)->latestOfMany();
    }

    public function scopeActiveOrders(Builder $query): Builder
    {
        return $query->where('laundry_status', '!=', LaundryStatus::Dibatalkan->value);
    }

    public function scopeByLaundryStatus(Builder $query, LaundryStatus $status): Builder
    {
        return $query->where('laundry_status', $status->value);
    }

    public function scopeByPaymentStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('payment_status', $status->value);
    }

    /**
     * Scope pesanan yang sudah siap diambil tetapi belum lunas (belum dibayar atau dibayar sebagian).
     */
    public function scopeReadyAndUnpaid(Builder $query): Builder
    {
        return $query->where('laundry_status', LaundryStatus::SiapDiambil->value)
            ->where('payment_status', '!=', PaymentStatus::Lunas->value);
    }

    /**
     * Scope pesanan yang sudah lunas tetapi belum diserahkan ke pelanggan (belum berstatus Selesai).
     */
    public function scopePaidNotCompleted(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Lunas->value)
            ->where('laundry_status', '!=', LaundryStatus::Selesai->value)
            ->where('laundry_status', '!=', LaundryStatus::Dibatalkan->value);
    }


    /**
     * Total nominal pembayaran valid yang telah masuk.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->validPayments()->sum('amount');
    }

    /**
     * Sisa saldo tagihan yang belum lunas.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0.0, (float) $this->total_amount - $this->total_paid);
    }

    /**
     * Periksa apakah transaksi pesanan telah berstatus lunas.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === PaymentStatus::Lunas;
    }

    /**
     * Periksa apakah transaksi pesanan telah dibatalkan.
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->laundry_status === LaundryStatus::Dibatalkan;
    }

    /**
     * Format Rupiah untuk total tagihan.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp' . number_format((float) $this->total_amount, 0, ',', '.');
    }

    /**
     * Format Rupiah untuk sisa tagihan.
     */
    public function getFormattedRemainingBalanceAttribute(): string
    {
        return 'Rp' . number_format($this->remaining_balance, 0, ',', '.');
    }

    /**
     * Periksa apakah pesanan memerlukan tahapan penyetrikaan berdasarkan rincian layanan.
     */
    public function requiresIroning(): bool
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return false;
        }

        return $items->contains(function (OrderItem $item) {
            return stripos($item->service_name_snapshot, 'setrika') !== false;
        });
    }

    /**
     * Periksa apakah pesanan memerlukan tahapan pencucian (misal layanan bukan murni Setrika Saja).
     */
    public function requiresWashing(): bool
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return true;
        }

        $allAreIronOnly = $items->every(function (OrderItem $item) {
            return stripos($item->service_name_snapshot, 'setrika saja') !== false;
        });

        return ! $allAreIronOnly;
    }

    /**
     * Dapatkan rekomendasi status logis berikutnya berdasarkan alur layanan pesanan.
     */
    public function getNextRecommendedStatus(): ?LaundryStatus
    {
        return match ($this->laundry_status) {
            LaundryStatus::Diterima => LaundryStatus::MenungguProses,
            LaundryStatus::MenungguProses => $this->requiresWashing()
                ? LaundryStatus::Dicuci
                : LaundryStatus::Disetrika,
            LaundryStatus::Dicuci => LaundryStatus::Dikeringkan,
            LaundryStatus::Dikeringkan => $this->requiresIroning()
                ? LaundryStatus::Disetrika
                : LaundryStatus::SiapDiambil,
            LaundryStatus::Disetrika => LaundryStatus::SiapDiambil,
            LaundryStatus::SiapDiambil => LaundryStatus::Selesai,
            LaundryStatus::Selesai, LaundryStatus::Dibatalkan => null,
        };
    }

    /**
     * Dapatkan daftar transisi status yang valid dari status saat ini.
     *
     * @return list<LaundryStatus>
     */
    public function getAvailableTransitions(): array
    {
        return $this->laundry_status->allowedTransitions();
    }

    /**
     * Periksa apakah pesanan dapat bertransisi ke status target tertentu.
     */
    public function canTransitionTo(LaundryStatus $target): bool
    {
        if ($this->laundry_status === $target) {
            return false;
        }

        return $this->laundry_status->canTransitionTo($target);
    }

    /**
     * Periksa apakah status pesanan berada dalam status terminal (Selesai atau Dibatalkan).
     */
    public function isTerminal(): bool
    {
        return $this->laundry_status->isTerminal();
    }
}

