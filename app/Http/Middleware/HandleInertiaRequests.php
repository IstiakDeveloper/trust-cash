<?php

namespace App\Http\Middleware;

use App\Models\PendingSale;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->loadMissing('role') : null,
            ],
            'counts' => [
                'pendingOrders' => fn () => $request->user()
                    ? PendingSale::query()->where('status', 'pending')->count()
                    : 0,
            ],
            'app_name' => config('app.name', 'TrustCash'),
            'business_name' => function () {
                try {
                    if (function_exists('tenant') && tenant()) {
                        return tenant('name') ?? config('app.name', 'TrustCash');
                    }
                    if (class_exists(\App\Models\Setting::class) && method_exists(\App\Models\Setting::class, 'get')) {
                        return \App\Models\Setting::get('business_name', config('app.name', 'TrustCash'));
                    }
                } catch (\Throwable $e) {
                    // Fail safely to config
                }
                return config('app.name', 'TrustCash');
            },
            'appUrl' => config('app.url'),
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'sale' => fn() => $request->session()->get('sale'),
            ],
        ];
    }

}
