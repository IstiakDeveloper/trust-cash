<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Central\TenantRegistrationController;
use App\Models\Tenant;
use App\Support\TenancyHost;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

class InitializeTenancyIfTenantDomain
{
    /**
     * Handle an incoming request.
     * Automatically initializes tenant context when request arrives on a tenant domain/subdomain.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHost());
        $baseDomain = strtolower(TenantRegistrationController::resolveBaseDomain($request));

        if (TenancyHost::isCentralRequest($request)) {
            return $next($request);
        }

        $expectedHost = null;
        $subdomain = explode('.', $host)[0];
        if ($subdomain !== '' && $subdomain !== $host) {
            $expectedHost = TenantRegistrationController::tenantFqdn($subdomain, $request);
        }

        $domainRecord = Domain::where('domain', $host)->first();

        if (!$domainRecord && $expectedHost && $host === $expectedHost) {
            $tenant = Tenant::find($subdomain);
            if ($tenant) {
                $domainRecord = $tenant->domains()->where('domain', $expectedHost)->first();
                if (!$domainRecord) {
                    $domainRecord = $tenant->domains()->create(['domain' => $expectedHost]);
                }
                $tenant->domains()
                    ->where('domain', '!=', $expectedHost)
                    ->get()
                    ->filter(fn ($domain) => TenancyHost::isMalformedShopDomain($domain->domain) || !str_ends_with($domain->domain, '.'.$baseDomain))
                    ->each(fn ($domain) => $domain->delete());
            }
        }

        if (!$domainRecord) {
            if (str_ends_with($host, '.localhost')) {
                $altHost = str_replace('.localhost', '.127.0.0.1', $host);
                $domainRecord = Domain::where('domain', $altHost)->first();
            } elseif (str_ends_with($host, '.127.0.0.1')) {
                $altHost = str_replace('.127.0.0.1', '.localhost', $host);
                $domainRecord = Domain::where('domain', $altHost)->first();
            }
        }

        if (!$domainRecord && $subdomain) {
            $tenant = Tenant::find($subdomain);
            if ($tenant) {
                $domainRecord = $tenant->domains->firstWhere('domain', $expectedHost) ?? $tenant->domains->first();
            }
        }

        if ($domainRecord && $domainRecord->tenant) {
            $tenant = $domainRecord->tenant;

            if ($expectedHost && $domainRecord->domain !== $expectedHost && $host === $expectedHost) {
                $domainRecord->update(['domain' => $expectedHost]);
            }

            if (!tenancy()->initialized) {
                tenancy()->initialize($tenant);
            }

            if ($tenant->status === 'pending') {
                return response()->view('errors.store_pending', [
                    'tenant' => $tenant,
                ], 403);
            }

            if ($tenant->status === 'suspended') {
                return response()->view('errors.store_suspended', [
                    'tenant' => $tenant,
                ], 403);
            }

            if ($tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
                return response()->view('errors.store_suspended', [
                    'tenant' => $tenant,
                ], 402);
            }

            if ($this->isStaffPath($request)) {
                return redirect()->away(TenancyHost::centralOrigin($request).'/login');
            }
        }

        return $next($request);
    }

    private function isStaffPath(Request $request): bool
    {
        return $request->is(
            'login',
            'logout',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'dashboard',
            'admin',
            'admin/*',
            'super-admin',
            'super-admin/*',
            'impersonate',
            'impersonate/*',
            'profile',
            'profile/*'
        );
    }
}
