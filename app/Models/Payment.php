<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use Database\Factories\PaymentFactory;
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
            'method' => PaymentMethod::class,
            'status' => PaymentRecordStatus::class,
        ];
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

    public function isValid(): bool
    {
        return $this->status === PaymentRecordStatus::Valid;
    }

    public function isVoided(): bool
    {
        return $this->status === PaymentRecordStatus::Voided;
    }
}
