<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'image',
        'option_values',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'compare_price' => 'float',
        'cost_price' => 'float',
        'stock_quantity' => 'integer',
        'option_values' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->latest();
    }
}
