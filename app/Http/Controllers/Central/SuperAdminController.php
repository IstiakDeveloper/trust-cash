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
use Illuminate\Support\Facades\Storage;
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
            'business_name'  => 'required|string|max:255',
            'subdomain'      => 'required|string|min:3|max:50|alpha_dash|unique:tenants,id',
            'owner_name'     => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'password'       => 'required|string|min:4',
            'plan_id'        => 'required|exists:plans,id',
            'status'         => 'nullable|in:active,trial,pending',
            'trial_days'     => 'nullable|integer|min:0|max:365',
            'billing_cycle'  => 'nullable|in:monthly,yearly',
            'discount_type'  => 'nullable|in:none,percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_note'  => 'nullable|string|max:255',
            'custom_price'   => 'nullable|numeric|min:0',
        ]);

        $subdomain = Str::lower($validated['subdomain']);
        $plan = Plan::findOrFail($validated['plan_id']);
        $initialStatus = $validated['status'] ?? ($validated['trial_days'] > 0 ? 'trial' : 'active');
        $trialDays = (int) ($validated['trial_days'] ?? 14);
        $billingCycle = $validated['billing_cycle'] ?? 'monthly';
        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        $discountNote = $validated['discount_note'] ?? null;
        $customPrice = isset($validated['custom_price']) && $validated['custom_price'] !== '' ? (float) $validated['custom_price'] : null;

        $trialEndsAt = $initialStatus === 'trial' ? now()->addDays($trialDays) : null;

        $tenant = Tenant::create([
            'id'             => $subdomain,
            'name'           => $validated['business_name'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'] ?? null,
            'plan_id'        => $plan->id,
            'status'         => $initialStatus,
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'discount_note'  => $discountNote,
            'trial_ends_at'  => $trialEndsAt,
        ]);

        // Attach domain: normalize to localhost for local testing
        $host = request()->getHost();
        $isLocal = app()->environment('local') || in_array($host, ['127.0.0.1', 'localhost', '::1']);
        $baseDomain = $isLocal ? 'localhost' : $host;
        $fullDomain = "{$subdomain}.{$baseDomain}";

        $tenant->domains()->create(['domain' => $fullDomain]);
        if ($isLocal) {
            $tenant->domains()->create(['domain' => "{$subdomain}.127.0.0.1"]);
        }

        // Seed initial tenant admin user & default database
        try {
            $tenant->run(function () use ($validated, $subdomain) {
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
                    'name'     => $validated['owner_name'],
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
        } catch (\Throwable $e) {
            // Log tenant seeding warning
        }

        // Create subscription record
        $subStatus = $initialStatus === 'trial' ? 'trialing' : ($initialStatus === 'pending' ? 'pending' : 'active');
        $endsAt = $initialStatus === 'trial'
            ? now()->addDays($trialDays)
            : ($billingCycle === 'yearly' ? now()->addYear() : now()->addMonth());

        TenantSubscription::create([
            'tenant_id'      => $tenant->id,
            'plan_id'        => $plan->id,
            'status'         => $subStatus,
            'billing_cycle'  => $billingCycle,
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'discount_note'  => $discountNote,
            'custom_price'   => $customPrice,
            'starts_at'      => now(),
            'ends_at'        => $endsAt,
            'auto_renew'     => true,
        ]);

        return redirect()->route('super-admin.tenants.index')->with('success', "Store \"{$validated['business_name']}\" ({$fullDomain}) created successfully!");
    }

    /**
     * Approve a Pending Store Registration with Optional Discount
     */
    public function approveTenant(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'approval_type'  => 'required|in:trial,active',
            'days'           => 'nullable|integer|min:1|max:365',
            'plan_id'        => 'nullable|exists:plans,id',
            'billing_cycle'  => 'nullable|in:monthly,yearly',
            'discount_type'  => 'nullable|in:none,percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_note'  => 'nullable|string|max:255',
            'custom_price'   => 'nullable|numeric|min:0',
        ]);

        $days = (int) ($validated['days'] ?? 14);
        $planId = $validated['plan_id'] ?? $tenant->plan_id;
        $billingCycle = $validated['billing_cycle'] ?? 'monthly';
        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        $discountNote = $validated['discount_note'] ?? null;
        $customPrice = isset($validated['custom_price']) && $validated['custom_price'] !== '' ? (float) $validated['custom_price'] : null;

        $newStatus = $validated['approval_type'] === 'trial' ? 'trial' : 'active';
        $trialEndsAt = $validated['approval_type'] === 'trial' ? now()->addDays($days) : null;

        $tenant->update([
            'plan_id'        => $planId,
            'status'         => $newStatus,
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'discount_note'  => $discountNote,
            'trial_ends_at'  => $trialEndsAt,
        ]);

        $sub = $tenant->subscriptions()->latest()->first();
        $subStatus = $validated['approval_type'] === 'trial' ? 'trialing' : 'active';
        $startsAt = now();
        $endsAt = $validated['approval_type'] === 'trial'
            ? now()->addDays($days)
            : ($billingCycle === 'yearly' ? now()->addYear() : now()->addMonth());

        if ($sub) {
            $sub->update([
                'plan_id'        => $planId,
                'status'         => $subStatus,
                'billing_cycle'  => $billingCycle,
                'discount_type'  => $discountType,
                'discount_value' => $discountValue,
                'discount_note'  => $discountNote,
                'custom_price'   => $customPrice,
                'starts_at'      => $startsAt,
                'ends_at'        => $endsAt,
            ]);
        } else {
            TenantSubscription::create([
                'tenant_id'      => $tenant->id,
                'plan_id'        => $planId,
                'status'         => $subStatus,
                'billing_cycle'  => $billingCycle,
                'discount_type'  => $discountType,
                'discount_value' => $discountValue,
                'discount_note'  => $discountNote,
                'custom_price'   => $customPrice,
                'starts_at'      => $startsAt,
                'ends_at'        => $endsAt,
                'auto_renew'     => true,
            ]);
        }

        $discountMsg = '';
        if ($discountType === 'percentage' && $discountValue > 0) {
            $discountMsg = " with {$discountValue}% discount";
        } elseif ($discountType === 'fixed' && $discountValue > 0) {
            $discountMsg = " with ৳{$discountValue} discount";
        } elseif ($customPrice !== null && $customPrice > 0) {
            $discountMsg = " with custom price ৳{$customPrice}";
        }

        return redirect()->back()->with('success', "Store \"{$tenant->name}\" has been APPROVED and activated{$discountMsg}!");
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
     * Manually Update Plan or Subscription (including Discounts)
     */
    public function updateSubscription(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'billing_cycle'  => 'required|in:monthly,yearly',
            'status'         => 'required|in:active,trial,suspended',
            'discount_type'  => 'nullable|in:none,percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_note'  => 'nullable|string|max:255',
            'custom_price'   => 'nullable|numeric|min:0',
            'ends_at'        => 'nullable|date',
        ]);

        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        $discountNote = $validated['discount_note'] ?? null;
        $customPrice = isset($validated['custom_price']) && $validated['custom_price'] !== '' ? (float) $validated['custom_price'] : null;

        $tenant->update([
            'plan_id'        => $validated['plan_id'],
            'status'         => $validated['status'],
            'discount_type'  => $discountType,
            'discount_value' => $discountValue,
            'discount_note'  => $discountNote,
        ]);

        $sub = $tenant->subscriptions()->latest()->first();
        if ($sub) {
            $sub->update([
                'plan_id'        => $validated['plan_id'],
                'billing_cycle'  => $validated['billing_cycle'],
                'status'         => $validated['status'] === 'suspended' ? 'cancelled' : 'active',
                'discount_type'  => $discountType,
                'discount_value' => $discountValue,
                'discount_note'  => $discountNote,
                'custom_price'   => $customPrice,
                'ends_at'        => $validated['ends_at'] ? \Carbon\Carbon::parse($validated['ends_at']) : $sub->ends_at,
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
        $defaults = [
            'app_name'                => 'TrustCash',
            'app_tagline'             => 'Cloud POS & Accounting SaaS',
            'site_logo'               => '',
            'site_favicon'            => '',
            'currency_symbol'         => '৳',
            'currency_code'           => 'BDT',
            'default_trial_days'      => '14',
            'auto_approve_tenants'    => 'false',
            'nav_show_features'       => 'true',
            'nav_show_use_cases'      => 'true',
            'nav_show_pricing'        => 'true',
            'nav_show_faq'            => 'true',
            'nav_cta_text'            => '১৪ দিন ফ্রি ট্রায়াল শুরু করুন',
            'nav_cta_url'             => '/register-business',
            'whatsapp_number'         => '',
            'support_phone'           => '+880 1700-000000',
            'support_email'           => 'support@trustcash.com',
            'company_address'         => 'Dhaka, Bangladesh',
            'social_facebook'         => '',
            'social_youtube'          => '',
            'social_linkedin'         => '',
            'sslcommerz_store_id'     => '',
            'sslcommerz_store_passwd' => '',
            'sslcommerz_sandbox'      => 'false',
            'bkash_app_key'           => '',
            'bkash_app_secret'        => '',
            'bkash_username'          => '',
            'bkash_password'          => '',
            'bkash_sandbox'           => 'false',
        ];

        $settings = array_merge($defaults, Setting::getAll());

        return Inertia::render('Central/SuperAdmin/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update Central Platform Settings
     */
    public function updateSettings(Request $request)
    {
        try {
            // Handle site_logo file upload
            if ($request->hasFile('site_logo')) {
                $request->validate([
                    'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
                ]);
                $path = $request->file('site_logo')->store('branding', 'public');
                Setting::set('site_logo', '/storage/' . $path);
            } elseif ($request->boolean('remove_site_logo')) {
                Setting::set('site_logo', '');
            }

            // Handle site_favicon file upload
            if ($request->hasFile('site_favicon')) {
                $request->validate([
                    'site_favicon' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:2048',
                ]);
                $path = $request->file('site_favicon')->store('branding', 'public');
                Setting::set('site_favicon', '/storage/' . $path);
            } elseif ($request->boolean('remove_site_favicon')) {
                Setting::set('site_favicon', '');
            }

            $inputs = $request->except([
                '_token',
                'site_logo',
                'site_favicon',
                'remove_site_logo',
                'remove_site_favicon',
            ]);

            foreach ($inputs as $key => $value) {
                if ($value === true || $value === 'true' || $value === '1' || $value === 1) {
                    $value = 'true';
                } elseif ($value === false || $value === 'false' || $value === '0' || $value === 0) {
                    $value = 'false';
                } elseif ($value === null) {
                    $value = '';
                }
                Setting::set($key, (string) $value);
            }

            return redirect()->back()->with('success', "Platform settings updated successfully.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to update settings: " . $e->getMessage());
            return redirect()->back()->with('error', "Failed to update settings: " . $e->getMessage());
        }
    }
}
