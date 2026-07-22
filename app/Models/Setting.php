<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Simple key-value application settings (billing/VAT, future toggles). */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Per-request cache so pricing code can call get() freely. */
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        self::$cache ??= self::query()->pluck('value', 'key')->all();

        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $v = self::get($key);

        return $v === null ? $default : in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $v = self::get($key);

        return $v === null || ! is_numeric($v) ? $default : (float) $v;
    }
}
