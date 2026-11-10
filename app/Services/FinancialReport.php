<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExtraIncomeCategory;
use App\Models\FixedAsset;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialReport
{
    public function income(string $start, string $end): array
    {
        if (DB::table('sale_accounting_entries')->where('date', '<=', $end)->whereNull('cost')->exists()) {
            throw ValidationException::withMessages(['accounting' => 'Some physical sale lines have incomplete stock costing. Reconcile them before reporting profit.']);
        }
        $ledger = app(FinancialLedger::class);
        $period = $ledger->balances($end, $start);
        $cumulative = $ledger->balances($end);
        $profit = fn ($b) => bcsub(bcmul($ledger->total($b, 'sales'), '-1', 6), $ledger->total($b, 'cogs'), 6);
        $categories = collect(array_unique(array_merge(array_keys($period), array_keys($cumulative))))
            ->filter(fn ($key) => str_starts_with($key, 'other_income:'))->map(function ($key) use ($period, $cumulative) {
                $id = (int) substr($key, strlen('other_income:'));

                return ['name' => ExtraIncomeCategory::withTrashed()->find($id)?->name ?? 'Uncategorized',
                    'period' => bcmul($period[$key] ?? '0', '-1', 6), 'cumulative' => bcmul($cumulative[$key] ?? '0', '-1', 6)];
            })->values();
        $extra = ['period' => bcmul($ledger->total($period, 'other_income'), '-1', 6), 'cumulative' => bcmul($ledger->total($cumulative, 'other_income'), '-1', 6)];

        return [
            'sales_profit' => ['period' => $profit($period), 'cumulative' => $profit($cumulative)],
            'extra_income' => ['categories' => $categories, 'total' => $extra],
            'net_sales' => ['period' => bcmul($ledger->total($period, 'sales'), '-1', 6), 'cumulative' => bcmul($ledger->total($cumulative, 'sales'), '-1', 6)],
            'cost_of_sales' => ['period' => $ledger->total($period, 'cogs'), 'cumulative' => $ledger->total($cumulative, 'cogs')],
            'total' => ['period' => bcadd($profit($period), $extra['period'], 6), 'cumulative' => bcadd($profit($cumulative), $extra['cumulative'], 6)],
        ];
    }

    public function expenditure(string $start, string $end): array
    {
        $ledger = app(FinancialLedger::class);
        $period = $ledger->balances($end, $start);
        $cumulative = $ledger->balances($end);
        $categories = collect(array_unique(array_merge(array_keys($period), array_keys($cumulative))))
            ->filter(fn ($key) => str_starts_with($key, 'expense:') || $key === 'asset_value_expense')
            ->map(function ($key) use ($period, $cumulative) {
                return ['name' => $key === 'asset_value_expense' ? 'Depreciation / asset value changes' : (ExpenseCategory::withTrashed()->find((int) substr($key, 8))?->name ?? 'Uncategorized'),
                    'period' => $period[$key] ?? '0', 'cumulative' => $cumulative[$key] ?? '0'];
            })->values();

        return ['categories' => $categories, 'total' => [
            'period' => bcadd($ledger->total($period, 'expense'), $ledger->total($period, 'asset_value_expense'), 6),
            'cumulative' => bcadd($ledger->total($cumulative, 'expense'), $ledger->total($cumulative, 'asset_value_expense'), 6),
        ]];
    }

    public function balanceSheet(string $end): array
    {
        $ledger = app(FinancialLedger::class);
        $balances = $ledger->balances($end);
        $income = $this->income('0001-01-01', $end);
        $expenditure = $this->expenditure('0001-01-01', $end);
        $netProfit = bcsub($income['total']['cumulative'], $expenditure['total']['cumulative'], 6);
        $receivable = '0';
        $customerAdvances = '0';
        $payable = '0';
        $supplierAdvances = '0';
        foreach ($balances as $account => $value) {
            if (str_starts_with($account, 'receivable:')) {
                if (bccomp($value, '0', 6) >= 0) {
                    $receivable = bcadd($receivable, $value, 6);
                } else {
                    $customerAdvances = bcsub($customerAdvances, $value, 6);
                }
            } elseif (str_starts_with($account, 'payable:')) {
                if (bccomp($value, '0', 6) <= 0) {
                    $payable = bcsub($payable, $value, 6);
                } else {
                    $supplierAdvances = bcadd($supplierAdvances, $value, 6);
                }
            }
        }
        $controls = [];
        if ($end >= now()->toDateString()) {
            $customerDifference = '0';
            foreach (Customer::withTrashed()->get() as $customer) {
                $difference = bcsub($balances['receivable:'.$customer->id] ?? '0', (string) $customer->balance, 6);
                $customerDifference = bcadd($customerDifference, ltrim($difference, '-'), 6);
            }
            $supplierDifference = '0';
            foreach (Supplier::withTrashed()->get() as $supplier) {
                $difference = bcsub(bcmul($balances['payable:'.$supplier->id] ?? '0', '-1', 6), (string) $supplier->current_balance, 6);
                $supplierDifference = bcadd($supplierDifference, ltrim($difference, '-'), 6);
            }
            $cashDifference = '0';
            foreach (BankAccount::withTrashed()->get() as $account) {
                $difference = bcsub($balances['cash:'.$account->id] ?? '0', (string) $account->current_balance, 6);
                $cashDifference = bcadd($cashDifference, ltrim($difference, '-'), 6);
            }
            $controls = ['customer_control_difference' => $customerDifference, 'supplier_control_difference' => $supplierDifference, 'cash_projection_difference' => $cashDifference];
        }
        $fund = bcmul($ledger->total($balances, 'capital'), '-1', 6);
        $openingCapital = bcmul($ledger->total($balances, 'opening_equity'), '-1', 6);
        $tax = bcmul($ledger->total($balances, 'tax_payable'), '-1', 6);
        $loanBalance = $ledger->total($balances, 'loan_payable');
        $loansPayable = bccomp($loanBalance, '0', 6) < 0 ? bcmul($loanBalance, '-1', 6) : '0';
        $loansReceivable = bccomp($loanBalance, '0', 6) > 0 ? $loanBalance : '0';
        $cash = $ledger->total($balances, 'cash');
        $stock = $ledger->total($balances, 'inventory');
        $fixed = $ledger->total($balances, 'fixed_asset');
        $assets = bcadd(bcadd(bcadd($cash, $stock, 6), $fixed, 6), bcadd($receivable, $supplierAdvances, 6), 6);
        $assets = bcadd($assets, $loansReceivable, 6);
        $liabilities = '0';
        foreach ([$fund, $openingCapital, $netProfit, $payable, $customerAdvances, $tax, $loansPayable] as $value) {
            $liabilities = bcadd($liabilities, $value, 6);
        }
        $breakdown = [];
        foreach ($balances as $account => $value) {
            if (str_starts_with($account, 'fixed_asset:')) {
                $asset = FixedAsset::withTrashed()->find((int) substr($account, strlen('fixed_asset:')));
                $breakdown[] = ['name' => $asset?->name ?? 'Archived asset', 'code' => $asset?->asset_code, 'amount' => $value, 'items_count' => 0];
            }
        }

        return [
            'fund_and_liabilities' => [
                'fund' => ['period' => $fund], 'opening_capital' => ['period' => $openingCapital],
                'net_profit' => ['period' => $netProfit], 'supplier_due' => ['period' => $payable],
                'customer_advances' => ['period' => $customerAdvances], 'tax_payable' => ['period' => $tax], 'total' => $liabilities,
                'rows' => [
                    ['name' => 'Capital / fund', 'amount' => $fund], ['name' => 'Opening equity', 'amount' => $openingCapital],
                    ['name' => 'Retained / current earnings', 'amount' => $netProfit], ['name' => 'Supplier payables', 'amount' => $payable],
                    ['name' => 'Customer advances / credits', 'amount' => $customerAdvances], ['name' => 'Tax payable', 'amount' => $tax],
                    ['name' => 'Loans payable', 'amount' => $loansPayable],
                ],
            ],
            'property_and_assets' => [
                'bank_balance' => ['period' => $cash], 'customer_due' => ['period' => $receivable],
                'stock_value' => ['period' => $stock], 'supplier_advances' => ['period' => $supplierAdvances],
                'fixed_assets' => $fixed, 'fixed_assets_breakdown' => $breakdown, 'total' => $assets,
                'rows' => [
                    ['name' => 'Cash and bank', 'amount' => $cash], ['name' => 'Customer receivables', 'amount' => $receivable],
                    ['name' => 'Inventory', 'amount' => $stock], ['name' => 'Fixed assets (net)', 'amount' => $fixed],
                    ['name' => 'Supplier advances', 'amount' => $supplierAdvances],
                    ['name' => 'Loans receivable / advances', 'amount' => $loansReceivable],
                ],
            ],
            'reconciliation' => $controls + [
                'balance_sheet_difference' => bcsub($assets, $liabilities, 6),
                'unreconciled_clearing' => $ledger->total($balances, 'clearing'),
                'trial_balance_difference' => app(InventoryReport::class)->sum(collect($balances)->map(fn ($amount) => ['amount' => $amount]), 'amount'),
                'cash_difference' => bcsub($cash, app(CashReport::class)->balance($end), 6),
                'inventory_control_difference' => bcsub($stock, app(InventoryReport::class)->analysis('1000-01-01', $end)['totals']['available_stock_value'], 6),
            ],
        ];
    }
}
