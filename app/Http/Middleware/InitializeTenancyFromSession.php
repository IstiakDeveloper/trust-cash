<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenancyHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InitializeTenancyFromSession
{
    /**
     * On the main domain, open the shop database stored in the central session.
     * Super-admin pages stay on the central database.
     */
    public function handle(Request $request, Closure $next)
    {
        if (tenancy()->initialized || !TenancyHost::isCentralRequest($request)) {
            return $next($request);
        }

        if ($request->is('super-admin', 'super-admin/*')) {
            if ($request->session()->get('impersonator_id')) {
                return redirect('/admin/dashboard');
            }

            return $next($request);
        }

        $tenantId = $request->session()->get('active_tenant_id');

        if (!$tenantId) {
            if ($request->is('admin', 'admin/*')) {
                return redirect()->route('login');
            }

            return $next($request);
        }

        $tenant = Tenant::query()->find($tenantId);
        if (!$tenant || in_array($tenant->status, ['pending', 'suspended', 'cancelled'], true)) {
            $request->session()->forget(['active_tenant_id', 'impersonator_id']);
            Auth::guard('web')->logout();

            return redirect()->route('login');
        }

        tenancy()->initialize($tenant);

        return $next($request);
    }
}
