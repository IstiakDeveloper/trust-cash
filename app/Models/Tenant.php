<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'name',
        'email',
        'plan_id',
        'status',        // active, suspended, trial, cancelled
        'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'plan_id',
            'status',
            'trial_ends_at',
        ];
    }

    /**
     * Get the plan associated with the tenant.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the subscriptions for the tenant.
     */
    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class, 'tenant_id');
    }

    /**
     * Get the active subscription.
     */
    public function activeSubscription()
    {
        return $this->subscriptions()->where('status', 'active')->latest()->first();
    }

    /**
     * Check if the tenant is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the tenant is active (trial or paid).
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    /**
     * Check if the tenant's plan allows a feature.
     */
    public function canUseFeature(string $feature): bool
    {
        if (!$this->plan) return false;
        $features = $this->plan->features ?? [];
        return in_array($feature, $features);
    }

    /**
     * Get plan limit value.
     */
    public function getPlanLimit(string $limit): int
    {
        if (!$this->plan) return 0;
        return $this->plan->{$limit} ?? 0;
    }
}
