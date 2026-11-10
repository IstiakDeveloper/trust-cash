<?php

namespace App\Models;

use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use PostsAccountingAtomically;

    protected $fillable = [
        'return_number',
        'sale_id',
        'customer_id',
        'bank_account_id',
        'return_date',
        'total_amount',
        'refund_amount',
        'refund_status',
        'reason',
        'created_by',
        'request_key', 'request_hash',
    ];

    protected $casts = [
        'return_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
