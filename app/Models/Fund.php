<?php

namespace App\Models;

use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fund extends Model
{
    use HasFactory, SoftDeletes;
    use PostsAccountingAtomically;

    protected $fillable = [
        'bank_account_id',
        'bank_transaction_id',
        'type',
        'amount',
        'from_who',
        'description',
        'date',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
