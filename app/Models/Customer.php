<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'total_spent',
        'total_orders',
    ];

    protected $casts = [
        'total_spent' => 'float',
        'total_orders' => 'integer',
    ];

    protected $appends = ['name', 'full_address'];

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address_line_1, $this->city, $this->state, $this->postal_code, $this->country])
            ->filter()
            ->implode(', ');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function recalculateTotals(): void
    {
        $paidTotal = (float) $this->orders()->where('payment_status', 'paid')->sum('total');
        $ordersCount = $this->orders()->where('status', '!=', 'cancelled')->count();

        $this->update([
            'total_spent' => $paidTotal,
            'total_orders' => $ordersCount,
        ]);
    }
}
