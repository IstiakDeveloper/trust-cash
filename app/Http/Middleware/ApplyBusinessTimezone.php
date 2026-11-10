<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class ApplyBusinessTimezone
{
    public function handle(Request $request, Closure $next)
    {
        $original = config('app.timezone');
        $phpTimezone = date_default_timezone_get();
        $timezone = tenant() || $request->is('admin', 'admin/*')
            ? Setting::get('business_timezone', 'Asia/Dhaka') : $original;
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = $original;
        }
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        try {
            return $next($request);
        } finally {
            config(['app.timezone' => $original]);
            date_default_timezone_set($phpTimezone);
        }
    }
}
