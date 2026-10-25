<?php

declare(strict_types=1);

use App\Http\Controllers\Public\StorefrontController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| A tenant subdomain is the public shop only. Shop admin lives on the main domain.
| These routes run when the host is a tenant domain. Database queries use that shop.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    \App\Http\Middleware\EnsureTenantActive::class,
])->group(function () {
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

    Route::get('/', [StorefrontController::class, 'index'])->name('tenant.storefront');
});
