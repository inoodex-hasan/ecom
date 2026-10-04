<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'photos',
        'reviewer_name',
        'reviewer_email',
        'status',
        'is_verified_purchase',
        'merchant_reply',
        'merchant_replied_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'photos' => 'array',
        'is_verified_purchase' => 'boolean',
        'merchant_replied_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRating(Builder $query, int $stars): Builder
    {
        return $query->where('rating', $stars);
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
        $this->product?->updateRatingMetrics();
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
        $this->product?->updateRatingMetrics();
    }

    public function markAsSpam(): void
    {
        $this->update(['status' => 'spam']);
        $this->product?->updateRatingMetrics();
    }

    public function reply(string $replyText): void
    {
        $this->update([
            'merchant_reply' => $replyText,
            'merchant_replied_at' => now(),
        ]);
    }
}
