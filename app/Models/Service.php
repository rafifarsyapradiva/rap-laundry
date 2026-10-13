<?php

namespace App\Models;

use App\Enums\BillingUnit;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'billing_unit',
        'price',
        'estimated_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'billing_unit' => BillingUnit::class,
            'price' => 'decimal:2',
            'estimated_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk pencarian berdasarkan nama atau deskripsi layanan.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($term) {
            $subQuery->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Scope untuk memfilter berdasarkan satuan penagihan (kg, item, package).
     */
    public function scopeBillingUnit(Builder $query, ?string $unit): Builder
    {
        if (! $unit || $unit === 'all') {
            return $query;
        }

        $enum = BillingUnit::tryFrom($unit);
        if ($enum) {
            return $query->where('billing_unit', $enum->value);
        }

        return $query;
    }

    /**
     * Scope untuk memfilter berdasarkan status keaktifan (active, inactive, archived, all).
     */
    public function scopeStatusFilter(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'archived' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };
    }

    /**
     * Aksesor tarif berformat Rupiah lengkap dengan satuan penagihan (misal: Rp8.000 / Kg).
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = number_format((float) $this->price, 0, ',', '.');
        $unitLabel = $this->billing_unit?->label() ?? 'satuan';

        return "Rp{$price} / {$unitLabel}";
    }

    /**
     * Aksesor teks estimasi durasi pengerjaan yang mudah dipahami.
     */
    public function getEstimatedDurationTextAttribute(): string
    {
        if (! $this->estimated_hours) {
            return 'Sesuai antrean';
        }

        if ($this->estimated_hours < 24) {
            return "{$this->estimated_hours} jam";
        }

        $days = (int) ($this->estimated_hours / 24);
        $remainingHours = $this->estimated_hours % 24;

        if ($remainingHours === 0) {
            return "{$this->estimated_hours} jam ({$days} hari)";
        }

        return "{$this->estimated_hours} jam ({$days} hari {$remainingHours} jam)";
    }
}
