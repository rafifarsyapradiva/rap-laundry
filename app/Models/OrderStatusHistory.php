<?php

namespace App\Models;

use App\Enums\LaundryStatus;
use Database\Factories\OrderStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => LaundryStatus::class,
            'to_status' => LaundryStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Periksa apakah riwayat ini merupakan entri awal saat pesanan pertama kali dibuat.
     */
    public function getIsInitialAttribute(): bool
    {
        return $this->from_status === null;
    }

    /**
     * Periksa apakah riwayat ini merupakan tindakan koreksi status oleh Administrator.
     */
    public function getIsCorrectionAttribute(): bool
    {
        return str_starts_with((string) $this->note, '[KOREKSI]');
    }

    /**
     * Format tanggal dan jam kejadian dalam format Indonesia (dd-mm-yyyy HH:ii WIB).
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d-m-Y H:i') . ' WIB' : '-';
    }
}

