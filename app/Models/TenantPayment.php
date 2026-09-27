<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantPayment extends Model
{
    use CentralConnection;

    protected $table = 'tenant_payments';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'amount',
        'currency',
        'payment_method',   // sslcommerz, bkash, nagad, bank_transfer, cash
        'transaction_id',
        'gateway_response', // JSON
        'status',           // paid, pending, failed, refunded
        'paid_at',
        'note',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];

    /**
     * Get the tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription.
     */
    public function subscription()
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    /**
     * Scope: successful payments only.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
