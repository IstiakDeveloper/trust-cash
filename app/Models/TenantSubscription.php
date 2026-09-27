<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantSubscription extends Model
{
    use CentralConnection;

    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',           // active, past_due, cancelled, expired
        'billing_cycle',    // monthly, yearly
        'auto_renew',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the plan.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get payments for this subscription.
     */
    public function payments()
    {
        return $this->hasMany(TenantPayment::class, 'subscription_id');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    /**
     * Check if subscription is expiring soon (within X days).
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->isActive() && $this->ends_at->diffInDays(now()) <= $days;
    }

    /**
     * Scope: active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }
}
