<?php

namespace App\Models;

use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;
    use PostsAccountingAtomically;

    protected $fillable = [
        'expense_category_id',
        'bank_account_id',
        'bank_transaction_id',
        'amount',
        'description',
        'reference_no',
        'date',
        'attachment',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
