<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'usage_limit_total',
        'usage_limit_per_customer',
        'total_used',
        'is_active',
        'starts_at',
        'expires_at',
        'applicable_categories',
        'applicable_products',
    ];

    protected $casts = [
        'value' => 'float',
        'min_spend' => 'float',
        'max_discount' => 'float',
        'usage_limit_total' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'total_used' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
    ];

    protected $appends = [
        'status',
        'formatted_discount',
    ];

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, CouponUsage::class, 'coupon_id', 'id', 'id', 'order_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
    }

    public function scopeValidCode(Builder $query, string $code): Builder
    {
        return $query->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))]);
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'disabled';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->usage_limit_total !== null && $this->total_used >= $this->usage_limit_total) {
            return 'depleted';
        }

        return 'active';
    }

    public function getFormattedDiscountAttribute(): string
    {
        $symbol = Setting::get('currency_symbol', '৳');

        return match ($this->type) {
            'percentage' => rtrim(rtrim(number_format($this->value, 2), '0'), '.').'% OFF',
            'fixed_cart' => $symbol.number_format($this->value, 2).' OFF',
            'free_shipping' => 'Free Shipping',
            default => $symbol.number_format($this->value, 2),
        };
    }
}
