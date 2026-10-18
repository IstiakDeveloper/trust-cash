<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\TenantDatabaseService;
use App\Support\TenancyHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TenantRegistrationController extends Controller
{
    public static function resolveBaseDomain(?Request $request = null): string
    {
        return TenancyHost::central($request);
    }

    public static function resolvePortSuffix(?Request $request = null): string
    {
        return TenancyHost::portSuffix($request);
    }

    public static function tenantFqdn(string $subdomain, ?Request $request = null): string
    {
        return TenancyHost::shopHost($subdomain, self::resolveBaseDomain($request));
    }

    public static function tenantUrl(string $subdomain, ?Request $request = null): string
    {
        return TenancyHost::shopUrl($subdomain, self::resolveBaseDomain($request), $request);
    }

    public function showRegistrationForm(Request $request)
    {
        $selectedPlan = null;
        if ($request->filled('plan')) {
            $selectedPlan = Plan::where('slug', $request->plan)->first();
        }

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Central/RegisterBusiness', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan ?? $plans->first(),
            'baseDomain' => self::resolveBaseDomain($request),
        ]);
    }

    public function checkSubdomain(Request $request)
    {
        $subdomain = Str::slug($request->query('subdomain', ''));

        if (empty($subdomain)) {
            return response()->json(['available' => false, 'message' => 'Subdomain is required.']);
        }

        $reserved = ['admin', 'super-admin', 'api', 'www', 'mail', 'support', 'app', 'localhost'];
        if (in_array($subdomain, $reserved)) {
            return response()->json(['available' => false, 'message' => 'This subdomain is reserved.']);
        }

        $exists = Tenant::where('id', $subdomain)->exists();

        return response()->json([
            'available' => !$exists,
            'subdomain' => $subdomain,
            'message' => $exists ? 'Subdomain already taken.' : 'Subdomain is available!',
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'subdomain'     => 'required|string|min:3|max:50|alpha_dash|unique:tenants,id',
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'password'      => 'required|string|min:4|confirmed',
            'plan_id'       => 'required|exists:plans,id',
            'phone'         => 'nullable|string|max:50',
        ]);

        $subdomain = Str::lower($validated['subdomain']);
        $plan = Plan::findOrFail($validated['plan_id']);

        try {
            // 1. Create Tenant (Central) in PENDING status until Admin approves
            $tenant = Tenant::create([
                'id'            => $subdomain,
                'name'          => $validated['business_name'],
                'email'         => $validated['email'],
                'phone'         => $validated['phone'] ?? null,
                'plan_id'       => $plan->id,
                'status'        => 'pending', // Awaiting Super Admin approval
                'trial_ends_at' => null,      // Starts upon approval
            ]);

            // 2. Attach domain dynamically
            $baseDomain = self::resolveBaseDomain($request);
            $fullDomain = self::tenantFqdn($subdomain, $request);

            $tenant->domains()->create([
                'domain' => $fullDomain,
            ]);

            if ($baseDomain === 'localhost') {
                $tenant->domains()->create([
                    'domain' => $subdomain . '.127.0.0.1',
                ]);
            }

            // 3. Create initial pending subscription
            TenantSubscription::create([
                'tenant_id'     => $tenant->id,
                'plan_id'       => $plan->id,
                'starts_at'     => now(),
                'ends_at'       => now(),
                'status'        => 'pending',
                'billing_cycle' => 'monthly',
                'auto_renew'    => true,
            ]);

            // 4. Provision tenant database & seed admin user
            $tenant->run(function () use ($validated, $subdomain) {
                // Ensure default roles exist with required 'name' and 'slug'
                $adminRole = Role::firstOrCreate(
                    ['slug' => 'admin'],
                    [
                        'name'        => 'Admin',
                        'permissions' => null,
                    ]
                );
                Role::firstOrCreate(
                    ['slug' => 'manager'],
                    [
                        'name'        => 'Manager',
                        'permissions' => null,
                    ]
                );
                Role::firstOrCreate(
                    ['slug' => 'staff'],
                    [
                        'name'        => 'Staff',
                        'permissions' => null,
                    ]
                );

                User::create([
                    'name'     => $validated['name'],
                    'username' => $subdomain,
                    'email'    => $validated['email'],
                    'phone'    => $validated['phone'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'role_id'  => $adminRole->id,
                    'status'   => 1,
                ]);

                // Ensure default units and roles are seeded for this new shop owner
                (new \Database\Seeders\TenantDatabaseSeeder())->run();
            });

            $tenantUrl = self::tenantUrl($subdomain, $request);

            return response()->json([
                'success'       => true,
                'status'        => 'pending',
                'tenant_url'    => $tenantUrl,
                'subdomain'     => $fullDomain,
                'business_name' => $validated['business_name'],
                'message'       => 'আপনার রেজিস্ট্রেশন সফল হয়েছে! অ্যাকাউন্টটি বর্তমানে অনুমোদনের অপেক্ষায় রয়েছে (Pending Approval)। অ্যাডমিন যাচাই করে অনুমোদন দেওয়ার পর আপনি শপে প্রবেশ করতে পারবেন।',
            ]);
        } catch (\Throwable $e) {
            app(TenantDatabaseService::class)->rollbackFailedProvision($subdomain, $tenant ?? null);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}
