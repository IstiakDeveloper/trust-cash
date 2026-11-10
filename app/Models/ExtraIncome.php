<?php

namespace App\Models;

use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraIncome extends Model
{
    use HasFactory, SoftDeletes;
    use PostsAccountingAtomically;

    protected $fillable = [
        'bank_account_id',
        'bank_transaction_id',
        'category_id',
        'amount',
        'title',
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

    public function category(): BelongsTo  // Add this relationship
    {
        return $this->belongsTo(ExtraIncomeCategory::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
