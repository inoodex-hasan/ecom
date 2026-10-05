<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'total',
        'shipping_address',
        'billing_address',
        'notes',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'fraud_score',
        'fraud_risk_level',
        'fraud_status',
        'fraud_flags',
        'advance_delivery_charge',
        'advance_payment_method',
        'advance_transaction_id',
        'advance_payment_status',
        'fraud_checked_at',
        'fraud_notes',
        'courier_provider',
        'courier_consignment_id',
        'courier_tracking_code',
        'courier_status',
        'courier_cod_amount',
        'courier_dispatched_at',
        'courier_payload',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'shipping_cost' => 'float',
        'total' => 'float',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'fraud_score' => 'integer',
        'fraud_flags' => 'array',
        'advance_delivery_charge' => 'float',
        'fraud_checked_at' => 'datetime',
        'courier_cod_amount' => 'float',
        'courier_dispatched_at' => 'datetime',
        'courier_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-'.date('Y').'-'.strtoupper(Str::random(6));
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
