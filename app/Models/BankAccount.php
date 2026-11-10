<?php

namespace App\Models;

use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class BankAccount extends Model
{
    use PostsAccountingAtomically;
    use SoftDeletes;

    protected $fillable = [
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'opening_balance',
        'current_balance',
        'status', 'opened_on',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($account) => $account->opened_on ??= now()->toDateString());
        static::updating(function ($account) {
            if ($account->isDirty(['opening_balance', 'opened_on']) && $account->transactions()->exists()) {
                throw ValidationException::withMessages(['opening_balance' => 'Opening balances are locked after transactions. Post a dated adjustment instead.']);
            }
        });
        static::deleting(function ($account) {
            if ($account->transactions()->exists() || bccomp((string) $account->opening_balance, '0', 2) !== 0) {
                throw ValidationException::withMessages(['account' => 'Archive this account by changing its status; its posted history must be retained.']);
            }
        });
    }

    /**
     * Scope a query to only include active bank accounts.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', true);
    }

    // Relationships
    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function salePayments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function updateBalance($amount, $type = 'deposit')
    {
        $this->current_balance = $type === 'deposit'
            ? $this->current_balance + $amount
            : $this->current_balance - $amount;
        $this->save();
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
