<?php

namespace App\Models;

use App\Services\SaleAccounting;
use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use PostsAccountingAtomically;
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'customer_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'paid',
        'due',
        'payment_status',
        'note',
        'created_by', 'business_date', 'bank_account_id', 'request_key', 'request_hash',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($sale) => $sale->business_date ??= ($sale->created_at?->toDateString() ?? now()->toDateString()));
        static::deleting(function ($sale) {
            foreach ($sale->saleItems as $item) {
                app(SaleAccounting::class)->reverse($item);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function salePayments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class); // Adjust the relationship type and model as necessary
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
