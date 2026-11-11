<?php

namespace App\Models;

use App\Services\CashTransactionCategory;
use App\Services\LegacyInventoryCarryForward;
use App\Traits\PostsAccountingAtomically;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BankTransaction extends Model
{
    use PostsAccountingAtomically;
    use SoftDeletes;

    protected $fillable = [
        'bank_account_id',
        'transaction_type',
        'amount',
        'running_balance',
        'description',
        'date',
        'created_by', 'category', 'transfer_group', 'counter_account', 'classification_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date:Y-m-d',
    ];

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            app(LegacyInventoryCarryForward::class)->ensure();
            $ids = array_filter(array_unique([$this->getRawOriginal('bank_account_id'), $this->bank_account_id]));
            sort($ids);
            BankAccount::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            return parent::save($options);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            app(LegacyInventoryCarryForward::class)->ensure();
            BankAccount::withTrashed()->whereKey($this->bank_account_id)->lockForUpdate()->firstOrFail();

            return parent::delete();
        });
    }

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            $transaction->category ??= CashTransactionCategory::infer($transaction->description);
        });
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
