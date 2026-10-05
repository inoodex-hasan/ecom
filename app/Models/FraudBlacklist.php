<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'list_type',
        'reason',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBlacklist($query)
    {
        return $query->where('list_type', 'blacklist');
    }

    public function scopeWhitelist($query)
    {
        return $query->where('list_type', 'whitelist');
    }

    public static function isBlacklisted(string $type, ?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return static::where('type', $type)
            ->where('value', trim($value))
            ->where('list_type', 'blacklist')
            ->exists();
    }

    public static function isWhitelisted(string $type, ?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return static::where('type', $type)
            ->where('value', trim($value))
            ->where('list_type', 'whitelist')
            ->exists();
    }
}
