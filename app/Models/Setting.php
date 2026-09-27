<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        try {
            return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
                if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    return $default;
                }
                $setting = static::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, $value): void
    {
        try {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget("setting.{$key}");
        } catch (\Throwable $e) {
            // Log or ignore during migration/bootstrapping
        }
    }

    public static function getAll(): array
    {
        return static::pluck('value', 'key')->toArray();
    }
}
