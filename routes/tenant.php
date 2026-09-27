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
