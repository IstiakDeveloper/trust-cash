<?php

use App\Http\Middleware\ApplyBusinessTimezone;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InitializeTenancyFromSession;
use App\Http\Middleware\InitializeTenancyIfTenantDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'superadmin' => EnsureSuperAdmin::class,
        ]);

        $middleware->web(prepend: [
            InitializeTenancyIfTenantDomain::class,
        ]);

        $middleware->web(append: [
            InitializeTenancyFromSession::class,
            ApplyBusinessTimezone::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->appendToPriorityList(
            StartSession::class,
            InitializeTenancyFromSession::class,
        );

        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            '/payment/sslcommerz/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
