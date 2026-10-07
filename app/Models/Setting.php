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
            try {
                Cache::forget(static::getCacheKey($key));
                Cache::forget("setting.{$key}");
            } catch (\Throwable $ce) {
                // Ignore cache forget errors if cache driver doesn't support tags
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Setting::set error for [{$key}]: " . $e->getMessage());
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

    /**
     * Get a setting strictly from the Central (Landlord) database,
     * even when running inside a Tenant context.
     */
    public static function getCentral(string $key, $default = null)
    {
        if (isset(static::$runtimeCache['central'][$key])) {
            return static::$runtimeCache['central'][$key];
        }

        try {
            $centralConn = config('tenancy.database.central_connection', 'mysql');
            $val = \Illuminate\Support\Facades\DB::connection($centralConn)
                ->table('settings')
                ->where('key', $key)
                ->value('value');

            $result = ($val !== null && $val !== '') ? $val : $default;
            static::$runtimeCache['central'][$key] = $result;
            return $result;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
