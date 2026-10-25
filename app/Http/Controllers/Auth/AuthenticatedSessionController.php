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
use Illuminate\Validation\ValidationException;
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

        // 1. Central domain: super admin stays central, shop staff stay on this same host.
        if (!tenancy()->initialized) {
            $superAdmin = $isEmail
                ? User::where('email', $loginInput)->first()
                : User::where('username', $loginInput)->orWhere('email', 'like', "{$loginInput}@%")->first();

            if ($superAdmin && Hash::check($password, $superAdmin->password)) {
                $roleSlug = $superAdmin->role?->slug ?? '';
                if (in_array($roleSlug, ['super-admin', 'superadmin'])) {
                    $request->authenticate();
                    $request->session()->regenerate();
                    $request->session()->forget(['active_tenant_id', 'impersonator_id']);

                    return redirect()->intended(route('super-admin.dashboard', absolute: false));
                }
            }

            $tenant = $isEmail
                ? Tenant::where('email', $loginInput)->first()
                : Tenant::find($loginInput);

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
                $tenantUserId = null;

                try {
                    $tenant->run(function () use ($loginInput, $password, &$tenantUserId) {
                        $user = User::where('email', $loginInput)
                            ->orWhere('username', $loginInput)
                            ->orWhere('email', 'like', "{$loginInput}@%")
                            ->first();

                        if ($user && Hash::check($password, $user->password)) {
                            $tenantUserId = $user->id;
                        }
                    });
                } catch (\Throwable $e) {}

                if ($tenantUserId) {
                    if ($tenant->status === 'pending') {
                        throw ValidationException::withMessages([
                            'email' => "আপনার শপটি '{$tenant->name}' এখনও অনুমোদনের অপেক্ষায় রয়েছে (Pending Approval)। অ্যাডমিনের অনুমোদনের পর আপনি লগইন করতে পারবেন।",
                        ]);
                    }

                    if ($tenant->status === 'suspended') {
                        throw ValidationException::withMessages([
                            'email' => "আপনার শপটি '{$tenant->name}' সাময়িকভাবে স্থগিত করা হয়েছে। সহায়তার জন্য সুপার অ্যাডমিনের সাথে যোগাযোগ করুন।",
                        ]);
                    }

                    return $this->loginShopUser($request, $tenant, (int) $tenantUserId);
                }

                throw ValidationException::withMessages([
                    'password' => 'পাসওয়ার্ড ভুল হয়েছে। সঠিক পাসওয়ার্ড দিয়ে আবার চেষ্টা করুন।',
                ]);
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
     * Log a shop user into the main domain and point later requests at that shop database.
     */
    public function loginShopUser(Request $request, Tenant $tenant, int $userId, ?int $impersonatorId = null): RedirectResponse
    {
        if (!tenancy()->initialized) {
            tenancy()->initialize($tenant);
        }

        $shopUser = User::query()->find($userId);
        if (!$shopUser) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            throw ValidationException::withMessages([
                'email' => 'এই শপের অ্যাডমিন অ্যাকাউন্ট পাওয়া যায়নি।',
            ]);
        }

        Auth::login($shopUser, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('active_tenant_id', $tenant->getTenantKey());

        if ($impersonatorId) {
            $request->session()->put('impersonator_id', $impersonatorId);

            return redirect('/admin/dashboard');
        }

        $request->session()->forget('impersonator_id');

        return redirect()->intended('/admin/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget(['active_tenant_id', 'impersonator_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
