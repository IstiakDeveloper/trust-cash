<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\FixedAssetItem;
use App\Models\Fund;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\FinancialLedger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AccountingObserver
{
    public function created(Model $model): void
    {
        $this->post($model);
    }

    public function updated(Model $model): void
    {
        // Later collections have their own documents; don't repost the initial sale payment.
        if ($model instanceof Sale && ! $model->wasChanged(['subtotal', 'discount', 'total', 'customer_id'])) {
            return;
        }
        $this->post($model);
    }

    public function deleted(Model $model): void
    {
        app(FinancialLedger::class)->reverse($model->getTable(), $model->id);
        if ($model instanceof Sale) {
            app(FinancialLedger::class)->reverse('sale_tax', $model->id);
        }
    }

    public function restored(Model $model): void
    {
        $this->post($model);
    }

    public function post(Model $model): void
    {
        $ledger = app(FinancialLedger::class);
        $date = $model->created_at?->toDateString() ?? now()->toDateString();
        $type = $model->getTable();
        if ($model instanceof BankAccount) {
            $ledger->pair($type, $model->id, 'cash:'.$model->id, 'opening_equity', $model->opening_balance, $model->opened_on ?? $date);
        } elseif ($model instanceof BankTransaction) {
            $amount = $model->transaction_type === 'in' ? (string) $model->amount : bcmul((string) $model->amount, '-1', 6);
            $ledger->pair($type, $model->id, 'cash:'.$model->bank_account_id, $model->counter_account ?: 'clearing', $amount, $model->date->toDateString(), $model->category);
        } elseif ($model instanceof Sale) {
            $date = $model->business_date ?? $date;
            $ledger->pair($type, $model->id, 'clearing', 'receivable:'.($model->customer_id ?? 0), $model->paid, $date, 'sale_collection', $model->bank_account_id);
            $ledger->pair('sale_tax', $model->id, 'receivable:'.($model->customer_id ?? 0), 'tax_payable', $model->tax ?? 0, $date);
        } elseif ($model instanceof Purchase) {
            $ledger->pair($type, $model->id, 'clearing', 'payable:'.$model->supplier_id, $model->total_amount, $model->purchase_date->toDateString());
        } elseif ($model instanceof SalePayment) {
            $sale = Sale::withTrashed()->findOrFail($model->sale_id);
            $ledger->pair($type, $model->id, 'clearing', 'receivable:'.($sale->customer_id ?? 0), $model->amount, $date, 'sale_collection', $model->bank_account_id);
        } elseif ($model instanceof SupplierPayment) {
            $ledger->pair($type, $model->id, 'payable:'.$model->supplier_id, 'clearing', $model->amount, $model->payment_date->toDateString());
        } elseif ($model instanceof Expense) {
            $ledger->pair($type, $model->id, 'expense:'.($model->expense_category_id ?? 0), 'clearing', $model->amount, Carbon::parse($model->date)->toDateString());
        } elseif ($model instanceof ExtraIncome) {
            $ledger->pair($type, $model->id, 'clearing', 'other_income:'.($model->category_id ?? 0), $model->amount, Carbon::parse($model->date)->toDateString());
        } elseif ($model instanceof Fund) {
            $amount = $model->type === 'in' ? (string) $model->amount : bcmul((string) $model->amount, '-1', 6);
            $ledger->pair($type, $model->id, 'clearing', 'capital', $amount, Carbon::parse($model->date)->toDateString());
        } elseif ($model instanceof Supplier) {
            $ledger->pair($type, $model->id, 'opening_equity', 'payable:'.$model->id, $model->opening_balance ?? 0, $date);
        } elseif ($model instanceof FixedAssetItem) {
            $value = $model->status === 'disposed' ? '0' : (string) $model->current_value;
            $ledger->sync($type, $model->id, [
                ['account' => 'fixed_asset:'.$model->fixed_asset_id, 'amount' => $value],
                ['account' => 'asset_value_expense', 'amount' => bcsub((string) $model->purchase_price, $value, 6)],
                ['account' => 'clearing', 'amount' => bcmul((string) $model->purchase_price, '-1', 6)],
            ], $model->purchase_date->toDateString());
        }
    }
}
