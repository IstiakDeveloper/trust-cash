<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are loaded when a request is made to a tenant domain/subdomain.
| All database queries within these routes automatically target the tenant's DB!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    \App\Http\Middleware\EnsureTenantActive::class,
])->group(function () {
    // Serve dynamic favicon for tenant domains
    Route::get('/favicon.ico', function () {
        try {
            $favicon = \App\Models\Setting::getCentral('site_favicon', '');
            if (!empty($favicon)) {
                $path = base_path('storage/app/public/' . ltrim(str_replace('/storage/', '', $favicon), '/'));
                if (file_exists($path)) {
                    return response()->file($path);
                }
            }
        } catch (\Throwable $e) {}
        if (file_exists(public_path('favicon.png'))) {
            return response()->file(public_path('favicon.png'));
        }
        abort(404);
    });

    // Serve tenant storage files (images, documents, etc.)
    Route::get('/storage/{path}', function (string $path) {
        $tenantFile = storage_path("app/public/{$path}");
        if (is_file($tenantFile)) {
            return response()->file($tenantFile);
        }

        $centralFile = base_path("storage/app/public/{$path}");
        if (is_file($centralFile)) {
            return response()->file($centralFile);
        }

        abort(404);
    })->where('path', '.*')->name('tenant.storage.file');

    Route::get('/impersonate/{token}', function ($token) {
        return \Stancl\Tenancy\Features\UserImpersonation::makeResponse($token);
    })->name('tenant.impersonate');

    Route::get('/', function () {
        return redirect()->route('login');
    });

    require __DIR__ . '/auth.php';

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', function () {
            return redirect('/admin/dashboard');
        })->name('tenant.dashboard');

        // Note: The admin routes in routes/web.php are also accessible in tenant context
    });
});
