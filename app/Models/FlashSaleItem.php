<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'flash_price',
        'discount_percentage',
        'quantity_limit',
        'sold_count',
    ];

    protected $casts = [
        'flash_price' => 'float',
        'discount_percentage' => 'float',
        'quantity_limit' => 'integer',
        'sold_count' => 'integer',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
