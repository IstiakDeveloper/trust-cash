<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\StorefrontController;
use App\Models\Plan;
use App\Support\TenancyHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        // 1. Bypass auto-redirect if explicitly requested (e.g. /?preview=1 or /?landing=1)
        $isExplicitPreview = $request->has('preview') || $request->has('landing');

        // 2. Smart auto-redirect for logged-in tenants and admins
        if (!$isExplicitPreview && Auth::check()) {
            $user = Auth::user();
            $roleSlug = $user->role?->slug ?? '';
            $roleName = $user->role?->name ?? '';

            $isSuperAdmin = in_array($roleSlug, ['super-admin', 'superadmin'], true)
                || str_contains(strtolower($roleName), 'super admin');

            if ($isSuperAdmin) {
                return redirect()->route('super-admin.dashboard');
            }

            return redirect()->route('admin.dashboard');
        }

        // 3. Show tenant storefront if visited on a tenant subdomain
        if (function_exists('tenancy') && tenancy()->initialized && !TenancyHost::isCentralRequest($request)) {
            return app(StorefrontController::class)->index($request);
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Central/Landing', [
            'plans' => $plans,
        ]);
    }
}
