<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): mixed
    {
        $loginInput = trim($request->input('email'));
        $password = $request->input('password');
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        // 1. If on central domain (root login)
        if (!tenancy()->initialized) {
            // Check if Super Admin
            $superAdmin = $isEmail 
                ? User::where('email', $loginInput)->first() 
                : User::where('username', $loginInput)->orWhere('email', 'like', "{$loginInput}@%")->first();

            if ($superAdmin && Hash::check($password, $superAdmin->password)) {
                $roleSlug = $superAdmin->role?->slug ?? '';
                if (in_array($roleSlug, ['super-admin', 'superadmin'])) {
                    $request->authenticate();
                    $request->session()->regenerate();
                    return redirect()->intended(route('super-admin.dashboard', absolute: false));
                }
            }

            // Not super-admin: Check if email or username/id belongs to a registered Tenant store
            $tenant = $isEmail 
                ? Tenant::where('email', $loginInput)->first() 
                : Tenant::find($loginInput);

            // Fallback: search across all active/trial tenant databases for this user email or username!
            if (!$tenant) {
                foreach (Tenant::whereIn('status', ['active', 'trial'])->get() as $t) {
                    $userExists = false;
                    try {
                        $t->run(function () use ($loginInput, $isEmail, &$userExists) {
                            $userExists = User::where($isEmail ? 'email' : 'username', $loginInput)
                                ->orWhere('email', 'like', "{$loginInput}@%")
                                ->exists();
                        });
                    } catch (\Throwable $e) {}
                    if ($userExists) {
                        $tenant = $t;
                        break;
                    }
                }
            }

            if ($tenant) {
                $tenantUser = null;
                $passwordMatches = false;

                try {
                    $tenant->run(function () use ($loginInput, $password, &$tenantUser, &$passwordMatches) {
                        $user = User::where('email', $loginInput)
                            ->orWhere('username', $loginInput)
                            ->orWhere('email', 'like', "{$loginInput}@%")
                            ->first();

                        if ($user && Hash::check($password, $user->password)) {
                            $tenantUser = $user;
                            $passwordMatches = true;
                        }
                    });
                } catch (\Throwable $e) {}

                if ($passwordMatches && $tenantUser) {
                    if ($tenant->status === 'pending') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email' => "আপনার শপটি '{$tenant->name}' এখনও অনুমোদনের অপেক্ষায় রয়েছে (Pending Approval)। অ্যাডমিনের অনুমোদনের পর আপনি লগইন করতে পারবেন।",
                        ]);
                    }

                    if ($tenant->status === 'suspended') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email' => "আপনার শপটি '{$tenant->name}' সাময়িকভাবে স্থগিত করা হয়েছে। সহায়তার জন্য সুপার অ্যাডমিনের সাথে যোগাযোগ করুন।",
                        ]);
                    }

                    // Generate single-use impersonation token for auto-login
                    $token = tenancy()->impersonate($tenant, $tenantUser->id, '/admin/dashboard');

                    $domain = $tenant->domains->firstWhere('domain', 'like', '%.localhost')?->domain 
                        ?? $tenant->domains->first()?->domain 
                        ?? ($tenant->id . '.localhost');

                    if (str_ends_with($domain, '.127.0.0.1')) {
                        $domain = str_replace('.127.0.0.1', '.localhost', $domain);
                    }

                    $port = request()->getPort();
                    $portSuffix = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';
                    $scheme = request()->isSecure() ? 'https://' : 'http://';
                    $targetUrl = "{$scheme}{$domain}{$portSuffix}/impersonate/{$token->token}";

                    // Inertia location redirect seamlessly navigates to the tenant store and logs in!
                    return Inertia::location($targetUrl);
                } else {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'password' => 'পাসওয়ার্ড ভুল হয়েছে। সঠিক পাসওয়ার্ড দিয়ে আবার চেষ্টা করুন।',
                    ]);
                }
            }
        }

        // 2. Standard authentication (either on tenant subdomain or superadmin on central)
        $request->authenticate();
        $request->session()->regenerate();

        $roleSlug = auth()->user()?->role?->slug ?? '';
        if (in_array($roleSlug, ['super-admin', 'superadmin'])) {
            return redirect()->intended(route('super-admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Redirect to login page of current domain
        return redirect()->route('login');
    }
}
