<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static array $runtimeCache = [];

    protected static function getCacheKey(string $key): string
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant('id') : 'central';
        return "tenant_{$tenantId}.setting.{$key}";
    }

    public static function get(string $key, $default = null)
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant('id') : 'central';
        if (isset(static::$runtimeCache[$tenantId][$key])) {
            return static::$runtimeCache[$tenantId][$key];
        }

        try {
            $setting = static::where('key', $key)->first();
            $val = ($setting && $setting->value !== null && $setting->value !== '') ? $setting->value : $default;
            static::$runtimeCache[$tenantId][$key] = $val;
            return $val;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, $value): void
    {
        try {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
            $tenantId = function_exists('tenant') && tenant() ? tenant('id') : 'central';
            static::$runtimeCache[$tenantId][$key] = $value;
            Cache::forget(static::getCacheKey($key));
            Cache::forget("setting.{$key}");
        } catch (\Throwable $e) {
            // Log or ignore during migration/bootstrapping
        }
    }

    public static function getAll(): array
    {
        try {
            return static::pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
