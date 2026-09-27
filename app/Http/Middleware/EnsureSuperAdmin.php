<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        // Allow if user role slug is 'super-admin' or 'superadmin' or role name has 'Super Admin'
        $roleSlug = $user->role?->slug ?? '';
        $roleName = $user->role?->name ?? '';

        if (!in_array($roleSlug, ['super-admin', 'superadmin']) && !str_contains(strtolower($roleName), 'super admin')) {
            abort(403, 'Unauthorized access: Super Admin privileges are required.');
        }

        return $next($request);
    }
}