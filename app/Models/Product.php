<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'product_type',
        'name',
        'slug',
        'sku',
        'barcode',
        'short_description',
        'description',
        'price',
        'compare_price',
        'cost_price',
        'unit',
        'min_order_quantity',
        'quantity_step',
        'unit_coverage_value',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'attributes',
        'has_variants',
        'status',
        'is_featured',
        'badge_label',
        'is_hot',
        'is_trending',
        'is_new_arrival',
        'rating_avg',
        'rating_count',
        'primary_image',
    ];

    protected $casts = [
        'price' => 'float',
        'compare_price' => 'float',
        'cost_price' => 'float',
        'min_order_quantity' => 'float',
        'quantity_step' => 'float',
        'unit_coverage_value' => 'float',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'attributes' => 'array',
        'has_variants' => 'boolean',
        'is_featured' => 'boolean',
        'is_hot' => 'boolean',
        'is_trending' => 'boolean',
        'is_new_arrival' => 'boolean',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name).'-'.Str::lower(Str::random(5));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->latest();
    }

    public function flashSaleItems(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function updateRatingMetrics(): void
    {
        $approved = $this->reviews()->where('status', 'approved');
        $count = $approved->count();
        $avg = $count > 0 ? round((float) $approved->avg('rating'), 2) : 0.00;

        $this->update([
            'rating_count' => $count,
            'rating_avg' => $avg,
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeHot(Builder $query): Builder
    {
        return $query->where('is_hot', true);
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    public function scopeNewArrival(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_new_arrival', true)
                ->orWhere('created_at', '>=', now()->subDays(30));
        });
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function adjustStock(int $amount): void
    {
        $this->increment('stock_quantity', $amount);
    }
}
