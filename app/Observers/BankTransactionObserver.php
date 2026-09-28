<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\BankTransaction;

class BankTransactionObserver
{
    /**
     * Handle the BankTransaction "creating" event.
     */
    public function creating(BankTransaction $transaction): void
    {
        // Get the latest transaction for this bank account (exclude soft deleted)
        $latestTransaction = BankTransaction::where('bank_account_id', $transaction->bank_account_id)
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $bank = BankAccount::find($transaction->bank_account_id);

        // Start with previous balance or bank opening balance
        $previousBalance = $latestTransaction ? (float) $latestTransaction->running_balance : ($bank ? (float) $bank->opening_balance : 0);

        // Calculate new running balance
        if ($transaction->transaction_type === 'in') {
            $transaction->running_balance = $previousBalance + (float) $transaction->amount;
        } else {
            $transaction->running_balance = $previousBalance - (float) $transaction->amount;
        }
    }

    /**
     * Handle the BankTransaction "created" event.
     * Update bank account current balance after transaction is created.
     */
    public function created(BankTransaction $transaction): void
    {
        $bankAccount = BankAccount::find($transaction->bank_account_id);

        if ($bankAccount) {
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance += (float) $transaction->amount;
            } else {
                $bankAccount->current_balance -= (float) $transaction->amount;
            }

            $bankAccount->saveQuietly(); // Save without triggering observers
        }
    }

    /**
     * Handle the BankTransaction "updated" event.
     */
    public function updated(BankTransaction $transaction): void
    {
        // If amount, transaction_type, or bank_account_id changed, we update bank balance
        if ($transaction->isDirty(['amount', 'transaction_type', 'bank_account_id'])) {
            $oldBankId = $transaction->getOriginal('bank_account_id');
            $newBankId = $transaction->bank_account_id;
            $oldAmount = (float) $transaction->getOriginal('amount');
            $oldType = $transaction->getOriginal('transaction_type');
            $newAmount = (float) $transaction->amount;
            $newType = $transaction->transaction_type;

            if ($oldBankId === $newBankId) {
                $bankAccount = BankAccount::find($newBankId);
                if ($bankAccount) {
                    // Reverse old transaction effect
                    if ($oldType === 'in') {
                        $bankAccount->current_balance -= $oldAmount;
                    } else {
                        $bankAccount->current_balance += $oldAmount;
                    }

                    // Apply new transaction effect
                    if ($newType === 'in') {
                        $bankAccount->current_balance += $newAmount;
                    } else {
                        $bankAccount->current_balance -= $newAmount;
                    }

                    $bankAccount->saveQuietly();
                }
            } else {
                // Different bank accounts
                $oldBank = BankAccount::find($oldBankId);
                if ($oldBank) {
                    if ($oldType === 'in') {
                        $oldBank->current_balance -= $oldAmount;
                    } else {
                        $oldBank->current_balance += $oldAmount;
                    }
                    $oldBank->saveQuietly();
                }

                $newBank = BankAccount::find($newBankId);
                if ($newBank) {
                    if ($newType === 'in') {
                        $newBank->current_balance += $newAmount;
                    } else {
                        $newBank->current_balance -= $newAmount;
                    }
                    $newBank->saveQuietly();
                }
            }
        }
    }

    /**
     * Handle the BankTransaction "deleted" event.
     */
    public function deleted(BankTransaction $transaction): void
    {
        $bankAccount = BankAccount::find($transaction->bank_account_id);

        if ($bankAccount) {
            // Reverse the transaction effect
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance -= $transaction->amount;
            } else {
                $bankAccount->current_balance += $transaction->amount;
            }

            $bankAccount->saveQuietly();
        }
    }

    /**
     * Handle the BankTransaction "restored" event.
     */
    public function restored(BankTransaction $transaction): void
    {
        $bankAccount = BankAccount::find($transaction->bank_account_id);

        if ($bankAccount) {
            // Reapply the transaction effect
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance += $transaction->amount;
            } else {
                $bankAccount->current_balance -= $transaction->amount;
            }

            $bankAccount->saveQuietly();
        }
    }
}
