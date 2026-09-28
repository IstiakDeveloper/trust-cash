<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stancl\Tenancy\Features\UserImpersonation;

class SuperAdminController extends Controller
{
    /**
     * Super Admin Overview Dashboard
     */
    public function dashboard()
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $trialTenants = Tenant::where('status', 'trial')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $pendingTenants = Tenant::where('status', 'pending')->count();
        $expiringSoon = Tenant::where('trial_ends_at', '<=', now()->addDays(7))
            ->where('trial_ends_at', '>=', now())
            ->count();

        // Calculate Monthly Recurring Revenue (MRR)
        $activeSubscriptions = TenantSubscription::where('status', 'active')->with('plan')->get();
        $mrr = 0;
        foreach ($activeSubscriptions as $sub) {
            if ($sub->plan) {
                if ($sub->billing_cycle === 'yearly') {
                    $mrr += ($sub->plan->price_yearly / 12);
                } else {
                    $mrr += $sub->plan->price_monthly;
                }
            }
        }

        $totalRevenue = TenantPayment::where('status', 'paid')->sum('amount');
        $pendingPaymentsCount = TenantPayment::where('status', 'pending')->count();

        // Plans distribution
        $plansDistribution = Plan::withCount(['tenants' => function ($q) {
            $q->where('status', 'active');
        }])->get(['id', 'name', 'tenants_count']);

        $recentTenants = Tenant::with(['plan', 'domains'])->latest()->take(6)->get();
        $recentPayments = TenantPayment::with(['tenant'])->latest()->take(6)->get();

        return Inertia::render('Central/SuperAdmin/Dashboard', [
            'stats' => [
                'total_tenants'          => $totalTenants,
                'active_tenants'         => $activeTenants,
                'trial_tenants'          => $trialTenants,
                'suspended_tenants'      => $suspendedTenants,
                'expiring_soon'          => $expiringSoon,
                'pending_payments_count' => $pendingPaymentsCount,
                'mrr'                    => round($mrr, 2),
                'arr'                    => round($mrr * 12, 2),
                'total_revenue'          => round($totalRevenue, 2),
            ],
            'plansDistribution' => $plansDistribution,
            'recentTenants'     => $recentTenants,
            'recentPayments'    => $recentPayments,
        ]);
    }

    /**
     * Tenants Directory
     */
    public function tenants(Request $request)
    {
        $query = Tenant::with(['plan', 'domains', 'subscriptions' => function ($q) {
            $q->latest()->take(1);
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();
        $plans = Plan::all();

        return Inertia::render('Central/SuperAdmin/Tenants', [
            'tenants' => $tenants,
            'plans'   => $plans,
            'filters' => $request->only(['search', 'status', 'plan_id']),
        ]);
    }

    /**
     * Show Tenant Details
     */
    public function showTenant(Tenant $tenant)
    {
        $tenant->load(['plan', 'domains', 'subscriptions.plan', 'payments']);

        // Fetch resource count from tenant DB if possible
        $resourceStats = [
            'products_count' => 0,
            'users_count'    => 0,
            'sales_count'    => 0,
        ];

        try {
            $tenant->run(function () use (&$resourceStats) {
                if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
                    $resourceStats['products_count'] = \Illuminate\Support\Facades\DB::table('products')->count();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                    $resourceStats['users_count'] = \Illuminate\Support\Facades\DB::table('users')->count();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('sales')) {
                    $resourceStats['sales_count'] = \Illuminate\Support\Facades\DB::table('sales')->count();
                }
            });
        } catch (\Throwable $e) {
            // Tenant DB might still be initializing or unavailable
        }

        $allPlans = Plan::all();

        return Inertia::render('Central/SuperAdmin/TenantShow', [
            'tenant'        => $tenant,
            'resourceStats' => $resourceStats,
            'allPlans'      => $allPlans,
        ]);
    }

    /**
     * Create Store Manually from Super Admin
     */
    public function createTenant(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'subdomain'     => 'required|alpha_dash|unique:domains,domain',
            'owner_name'    => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'password'      => 'required|string|min:4',
            'plan_id'       => 'required|exists:plans,id',
            'trial_days'    => 'required|integer|min:0|max:365',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $tenantId = Str::slug($validated['subdomain']);

        $tenant = Tenant::create([
            'id'            => $tenantId,
            'name'          => $validated['business_name'],
            'email'         => $validated['email'],
            'phone'         => $validated['phone'],
            'plan_id'       => $plan->id,
            'status'        => $validated['trial_days'] > 0 ? 'trial' : 'active',
            'trial_ends_at' => $validated['trial_days'] > 0 ? now()->addDays((int) $validated['trial_days']) : null,
        ]);

        // Attach domain
        $centralHost = parse_url(config('app.url', 'http://localhost:8000'), PHP_URL_HOST) ?? 'localhost';
        $fullDomain = "{$validated['subdomain']}.{$centralHost}";
        $tenant->domains()->create(['domain' => $fullDomain]);

        // Seed initial tenant admin user
        try {
            $tenant->run(function () use ($validated) {
                $adminRole = \App\Models\Role::firstOrCreate(
                    ['slug' => 'admin'],
                    [
                        'name'        => 'Admin',
                        'permissions' => null,
                    ]
                );
                \App\Models\Role::firstOrCreate(
                    ['slug' => 'manager'],
                    [
                        'name'        => 'Manager',
                        'permissions' => null,
                    ]
                );
                \App\Models\Role::firstOrCreate(
                    ['slug' => 'staff'],
                    [
                        'name'        => 'Staff',
                        'permissions' => null,
                    ]
                );

                \App\Models\User::create([
                    'name'       => $validated['owner_name'],
                    'email'      => $validated['email'],
                    'phone'      => $validated['phone'] ?? null,
                    'password'   => Hash::make($validated['password']),
                    'role_id'    => $adminRole->id,
                    'status'     => 1,
                ]);

                // Ensure default units and roles are seeded for this new shop owner
                (new \Database\Seeders\TenantDatabaseSeeder())->run();
            });
        } catch (\Throwable $e) {
            // Log tenant seeding warning
        }

        // Create subscription record
        TenantSubscription::create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $plan->id,
            'status'        => $validated['trial_days'] > 0 ? 'trialing' : 'active',
            'billing_cycle' => 'monthly',
            'starts_at'     => now(),
            'ends_at'       => $validated['trial_days'] > 0 ? now()->addDays((int) $validated['trial_days']) : now()->addMonth(),
        ]);

        return redirect()->route('super-admin.tenants.index')->with('success', "Store {$validated['business_name']} created successfully!");
    }

    /**
     * Toggle Store Active/Suspended Status
     */
    /**
     * Approve a Pending Store Registration
     */
    public function approveTenant(Request $request, Tenant $tenant)
    {
        $days = (int) $request->input('days', 14);

        $tenant->update([
            'status'        => 'trial',
            'trial_ends_at' => now()->addDays($days),
        ]);

        $sub = $tenant->subscriptions()->latest()->first();
        if ($sub) {
            $sub->update([
                'status'    => 'active',
                'starts_at' => now(),
                'ends_at'   => now()->addDays($days),
            ]);
        }

        return redirect()->back()->with('success', "Store \"{$tenant->name}\" has been APPROVED and activated for {$days} days!");
    }

    public function toggleTenantStatus(Request $request, Tenant $tenant)
    {
        $newStatus = $tenant->status === 'suspended' ? 'active' : 'suspended';
        $tenant->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Store status updated to {$newStatus}.");
    }

    /**
     * Extend Trial or Subscription Days
     */
    public function extendTrial(Request $request, Tenant $tenant)
    {
        $request->validate(['days' => 'required|integer|min:1|max:365']);

        $baseDate = ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
            ? $tenant->trial_ends_at
            : now();

        $tenant->update([
            'status'        => $tenant->status === 'suspended' ? 'trial' : $tenant->status,
            'trial_ends_at' => $baseDate->addDays((int) $request->days),
        ]);

        return redirect()->back()->with('success', "Trial/validity extended by {$request->days} days.");
    }

    /**
     * Manually Update Plan or Subscription
     */
    public function updateSubscription(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'status'        => 'required|in:active,trial,suspended',
            'ends_at'       => 'nullable|date',
        ]);

        $tenant->update([
            'plan_id' => $validated['plan_id'],
            'status'  => $validated['status'],
        ]);

        $sub = $tenant->subscriptions()->latest()->first();
        if ($sub) {
            $sub->update([
                'plan_id'       => $validated['plan_id'],
                'billing_cycle' => $validated['billing_cycle'],
                'status'        => $validated['status'] === 'suspended' ? 'cancelled' : 'active',
                'ends_at'       => $validated['ends_at'] ? \Carbon\Carbon::parse($validated['ends_at']) : $sub->ends_at,
            ]);
        }

        return redirect()->back()->with('success', "Store plan & subscription updated successfully.");
    }

    /**
     * Reset Tenant Shop Admin Password
     */
    public function resetPassword(Request $request, Tenant $tenant)
    {
        $request->validate([
            'password' => 'required|string|min:4',
        ]);

        $newPassword = Hash::make($request->password);

        try {
            $tenant->run(function () use ($newPassword) {
                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', 1)
                    ->orWhere('role_id', 1)
                    ->limit(1)
                    ->update(['password' => $newPassword]);
            });
            return redirect()->back()->with('success', "Store admin password reset successfully.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Could not reset password: " . $e->getMessage());
        }
    }

    /**
     * 1-Click Impersonation (Login as Shop Owner)
     */
    public function impersonate(Tenant $tenant)
    {
        $domainModel = $tenant->domains->first();
        if (!$domainModel) {
            return redirect()->back()->with('error', 'No domain associated with this store.');
        }

        $domain = $domainModel->domain;
        $port = request()->getPort();
        $scheme = request()->isSecure() ? 'https://' : 'http://';

        $hostWithPort = ($port && !in_array($port, [80, 443])) ? "{$domain}:{$port}" : $domain;

        try {
            $token = tenancy()->impersonate($tenant, 1, '/admin/dashboard');
            return redirect("{$scheme}{$hostWithPort}/impersonate/{$token->token}");
        } catch (\Throwable $e) {
            // Fallback direct redirect to login
            return redirect("{$scheme}{$hostWithPort}/login")->with('info', "Direct link to store login: {$hostWithPort}");
        }
    }

    /**
     * Plan Management
     */
    public function plans()
    {
        $plans = Plan::withCount('tenants')->orderBy('sort_order')->get();

        return Inertia::render('Central/SuperAdmin/Plans', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store New Plan
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price_monthly'  => 'required|numeric|min:0',
            'price_yearly'   => 'required|numeric|min:0',
            'max_users'      => 'required|integer|min:1',
            'max_products'   => 'required|integer|min:1',
            'max_branches'   => 'required|integer|min:1',
            'features'       => 'nullable|array',
            'is_active'      => 'required|boolean',
            'sort_order'     => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        Plan::create($validated);

        return redirect()->back()->with('success', "Plan {$validated['name']} created successfully.");
    }

    /**
     * Update Plan
     */
    public function updatePlan(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price_monthly'  => 'required|numeric|min:0',
            'price_yearly'   => 'required|numeric|min:0',
            'max_users'      => 'required|integer|min:1',
            'max_products'   => 'required|integer|min:1',
            'max_branches'   => 'required|integer|min:1',
            'features'       => 'nullable|array',
            'is_active'      => 'required|boolean',
            'sort_order'     => 'nullable|integer',
        ]);

        $plan->update($validated);

        return redirect()->back()->with('success', "Plan {$plan->name} updated successfully.");
    }

    /**
     * Delete Plan
     */
    public function destroyPlan(Plan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return redirect()->back()->with('error', "Cannot delete plan that has active stores assigned.");
        }

        $plan->delete();
        return redirect()->back()->with('success', "Plan deleted successfully.");
    }

    /**
     * Payments & Billing Ledger
     */
    public function payments(Request $request)
    {
        $query = TenantPayment::with(['tenant', 'subscription.plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Central/SuperAdmin/Payments', [
            'payments' => $payments,
            'filters'  => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Approve Pending/Manual Payment
     */
    public function approvePayment(TenantPayment $payment)
    {
        $payment->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        $tenant = $payment->tenant;
        if ($tenant) {
            $tenant->update(['status' => 'active']);

            if ($payment->subscription) {
                $months = $payment->subscription->billing_cycle === 'yearly' ? 12 : 1;
                $payment->subscription->update([
                    'status'    => 'active',
                    'starts_at' => now(),
                    'ends_at'   => now()->addMonths($months),
                ]);
            }
        }

        return redirect()->back()->with('success', "Payment {$payment->transaction_id} approved. Store activated!");
    }

    /**
     * Reject Pending Payment
     */
    public function rejectPayment(Request $request, TenantPayment $payment)
    {
        $payment->update([
            'status' => 'failed',
        ]);

        return redirect()->back()->with('success', "Payment {$payment->transaction_id} marked as rejected.");
    }

    /**
     * Central Platform Settings
     */
    public function settings()
    {
        $settings = Setting::getAll();

        return Inertia::render('Central/SuperAdmin/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update Central Platform Settings
     */
    public function updateSettings(Request $request)
    {
        $inputs = $request->except(['_token']);

        foreach ($inputs as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return redirect()->back()->with('success', "Platform settings updated successfully.");
    }
}
