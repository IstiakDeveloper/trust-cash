<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fixed_asset_id',
        'item_name',
        'purchase_date',
        'purchase_price',
        'current_value',
        'bank_account_id',
        'bank_transaction_id',
        'status',
        'description',
        'created_by'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2'
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
