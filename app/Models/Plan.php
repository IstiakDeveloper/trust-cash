<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Plan extends Model
{
    use CentralConnection, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_users',
        'max_products',
        'max_branches',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly'  => 'decimal:2',
        'price_yearly'   => 'decimal:2',
        'max_users'      => 'integer',
        'max_products'   => 'integer',
        'max_branches'   => 'integer',
        'features'       => 'array',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    /**
     * Available feature flags for plans.
     */
    public const FEATURES = [
        'purchase_management'   => 'Purchase & Supplier Management',
        'multi_branch'          => 'Multi-Branch Support',
        'excel_export'          => 'Excel Export',
        'api_access'            => 'API Access',
        'sale_returns'          => 'Sale Returns',
        'purchase_returns'      => 'Purchase Returns',
        'sms_notifications'     => 'SMS Notifications',
        'advanced_reports'      => 'Advanced Reports',
        'custom_invoice'        => 'Custom Invoice Template',
        'priority_support'      => 'Priority Support',
    ];

    /**
     * Get tenants on this plan.
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get subscriptions for this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Scope: only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the yearly discount percentage.
     */
    public function getYearlyDiscountAttribute(): int
    {
        if (!$this->price_monthly || !$this->price_yearly) return 0;
        $yearlyIfMonthly = $this->price_monthly * 12;
        return (int) round((($yearlyIfMonthly - $this->price_yearly) / $yearlyIfMonthly) * 100);
    }
}
