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
        'status',           // active, past_due, cancelled, expired, pending, trialing
        'billing_cycle',    // monthly, yearly
        'discount_type',    // none, percentage, fixed
        'discount_value',
        'discount_note',
        'custom_price',
        'auto_renew',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'auto_renew'     => 'boolean',
        'discount_value' => 'decimal:2',
        'custom_price'   => 'decimal:2',
    ];

    /**
     * Calculate discounted billing amount based on plan & discount settings
     */
    public function calculateAmount(?float $baseAmount = null): array
    {
        if ($baseAmount === null) {
            $baseAmount = $this->billing_cycle === 'yearly'
                ? (float) ($this->plan?->price_yearly ?? 0)
                : (float) ($this->plan?->price_monthly ?? 0);
        }

        if ($this->custom_price !== null && $this->custom_price > 0) {
            $discountAmount = max(0, $baseAmount - (float) $this->custom_price);
            return [
                'original_price'  => $baseAmount,
                'discount_type'   => 'custom',
                'discount_value'  => $discountAmount,
                'discount_amount' => round($discountAmount, 2),
                'final_price'     => round((float) $this->custom_price, 2),
            ];
        }

        $discountAmount = 0.0;
        if ($this->discount_type === 'percentage' && $this->discount_value > 0) {
            $discountAmount = ($baseAmount * (float) $this->discount_value) / 100;
        } elseif ($this->discount_type === 'fixed' && $this->discount_value > 0) {
            $discountAmount = (float) $this->discount_value;
        }

        $discountAmount = min($baseAmount, max(0, $discountAmount));
        $finalPrice = max(0, $baseAmount - $discountAmount);

        return [
            'original_price'  => $baseAmount,
            'discount_type'   => $this->discount_type ?? 'none',
            'discount_value'  => (float) ($this->discount_value ?? 0),
            'discount_amount' => round($discountAmount, 2),
            'final_price'     => round($finalPrice, 2),
        ];
    }

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
