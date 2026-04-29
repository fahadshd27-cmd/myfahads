<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public static function getString(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(self::cacheKey($key), function () use ($key, $default) {
            return self::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::getString($key, null);

        return is_null($value) ? $default : (int) $value;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::getString($key, null);
        if (is_null($value)) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function putString(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return 'app_setting:'.$key;
    }
}
