<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
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
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);

        // Check if current host is a tenant domain (i.e. not a central domain)
        if (!in_array($host, $centralDomains)) {
            // 1. Exact match in domains table
            $domainRecord = Domain::where('domain', $host)->first();

            // 2. Fallback: match .localhost with .127.0.0.1 or vice versa
            if (!$domainRecord) {
                if (str_ends_with($host, '.localhost')) {
                    $altHost = str_replace('.localhost', '.127.0.0.1', $host);
                    $domainRecord = Domain::where('domain', $altHost)->first();
                } elseif (str_ends_with($host, '.127.0.0.1')) {
                    $altHost = str_replace('.127.0.0.1', '.localhost', $host);
                    $domainRecord = Domain::where('domain', $altHost)->first();
                }
            }

            // 3. Fallback: Subdomain matches Tenant ID directly
            if (!$domainRecord) {
                $subdomain = explode('.', $host)[0];
                $tenant = Tenant::find($subdomain);
                if ($tenant) {
                    $domainRecord = $tenant->domains->first();
                }
            }

            if ($domainRecord && $domainRecord->tenant) {
                $tenant = $domainRecord->tenant;

                // Initialize tenancy
                if (!tenancy()->initialized) {
                    tenancy()->initialize($tenant);
                }

                // Check tenant subscription / status (skip for impersonation endpoint)
                if (!$request->is('impersonate/*')) {
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
                }

                // If visiting root '/' on tenant domain, redirect to dashboard or login
                if ($request->path() === '/' || $request->path() === '') {
                    return redirect('/admin/dashboard');
                }
            }
        }

        return $next($request);
    }
}
