<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

class BankTransactionObserver
{
    public function creating(BankTransaction $transaction): void
    {
        $transaction->running_balance = '0';
    }

    public function created(BankTransaction $transaction): void
    {
        $this->rebuild((int) $transaction->bank_account_id);
    }

    public function deleted(BankTransaction $transaction): void
    {
        $this->rebuild((int) $transaction->bank_account_id);
    }

    public function restored(BankTransaction $transaction): void
    {
        $this->rebuild((int) $transaction->bank_account_id);
    }

    public function updated(BankTransaction $transaction): void
    {
        $ids = array_unique([(int) $transaction->getRawOriginal('bank_account_id'), (int) $transaction->bank_account_id]);
        sort($ids);
        foreach ($ids as $id) {
            $this->rebuild($id);
        }
    }

    private function rebuild(int $accountId): void
    {
        $account = BankAccount::withTrashed()->whereKey($accountId)->lockForUpdate()->firstOrFail();
        $balance = (string) $account->opening_balance;
        $carry = DB::table('legacy_balance_carry_forwards')->where('account', 'cash:'.$accountId)->first();
        $carryApplied = false;
        foreach (DB::table('bank_transactions')->where('bank_account_id', $accountId)->whereNull('deleted_at')->orderBy('date')->orderBy('id')->get() as $row) {
            if ($carry && ! $carryApplied && $row->date >= $carry->date) {
                $balance = bcadd($balance, (string) $carry->difference, 2);
                $carryApplied = true;
            }
            $balance = $row->transaction_type === 'in' ? bcadd($balance, (string) $row->amount, 2) : bcsub($balance, (string) $row->amount, 2);
            DB::table('bank_transactions')->where('id', $row->id)->update(['running_balance' => $balance]);
        }
        if ($carry && ! $carryApplied) {
            $balance = bcadd($balance, (string) $carry->difference, 2);
        }
        DB::table('bank_accounts')->where('id', $accountId)->update(['current_balance' => $balance]);
    }
}
