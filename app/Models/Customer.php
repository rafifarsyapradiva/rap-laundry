<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk pencarian multi-kriteria (nama, nomor telepon, atau kode pelanggan).
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Hilangkan strip/spasi untuk pencarian variasi nomor telepon
        $cleanPhone = preg_replace('/[\s\-\.]/', '', $term);

        return $query->where(function (Builder $subQuery) use ($term, $cleanPhone) {
            $subQuery->where('name', 'like', "%{$term}%")
                ->orWhere('customer_code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");

            if ($cleanPhone !== '' && $cleanPhone !== $term) {
                $subQuery->orWhere('phone', 'like', "%{$cleanPhone}%");
            }
        });
    }

    /**
     * Scope untuk memfilter berdasarkan status (active, inactive, archived, all).
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
     * Aksesor nomor telepon dengan format spasi/strip yang nyaman dibaca kasir.
     */
    public function getFormattedPhoneAttribute(): string
    {
        $digits = preg_replace('/\D/', '', $this->phone);
        $len = strlen($digits);

        if ($len >= 10 && $len <= 13 && str_starts_with($digits, '08')) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4, 4) . '-' . substr($digits, 8);
        }

        return $this->phone;
    }
}
