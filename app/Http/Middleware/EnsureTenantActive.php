<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Handle an incoming request on tenant subdomains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('tenant') && $tenant = tenant()) {
            // Check if tenant is pending admin approval
            if ($tenant->status === 'pending') {
                return response()->view('errors.store_pending', ['tenant' => $tenant], 403);
            }

            // Check if tenant is suspended
            if ($tenant->status === 'suspended') {
                return response()->view('errors.store_suspended', ['tenant' => $tenant], 403);
            }

            // Check if trial expired
            if ($tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
                return response()->view('errors.store_suspended', ['tenant' => $tenant], 402);
            }
        }

        return $next($request);
    }
}