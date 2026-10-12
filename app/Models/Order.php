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
}
