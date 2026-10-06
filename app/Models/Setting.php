<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    protected static ?array $runtimeCache = null;

    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::$runtimeCache === null) {
            static::$runtimeCache = static::all()->keyBy('key')->all();
        }

        $setting = static::$runtimeCache[$key] ?? null;
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => is_array($setting->value) ? $setting->value : json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::clearRuntimeCache();

        if ($type === 'json' && ! is_string($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'type' => $type, 'group' => $group]
        );
    }
}
