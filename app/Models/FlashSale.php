<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class FlashSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'banner_image',
        'description',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($flashSale) {
            if (empty($flashSale->slug)) {
                $flashSale->slug = Str::slug($flashSale->title).'-'.Str::lower(Str::random(5));
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, FlashSaleItem::class, 'flash_sale_id', 'id', 'id', 'product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function getStatusAttribute(): string
    {
        $now = now();
        if (! $this->is_active) {
            return 'inactive';
        }
        if ($this->starts_at > $now) {
            return 'upcoming';
        }
        if ($this->ends_at < $now) {
            return 'expired';
        }

        return 'running';
    }
}
