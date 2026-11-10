<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\User;
use Carbon\Carbon;

class BankReporting
{
    public function accounts(string $start, string $end, ?int $id = null): array
    {
        $cash = app(CashReport::class);
        $ledger = app(FinancialLedger::class);
        $calculator = app(InventoryReport::class);
        $accounts = BankAccount::withTrashed()->when($id, fn ($q) => $q->whereKey($id))->get();
        if ($id && $accounts->isEmpty()) {
            abort(404);
        }
        $opening = $ledger->balances(Carbon::parse($start)->subDay()->toDateString());
        $closing = $ledger->balances($end);
        $movements = $cash->movements($start, $end, $id);
        $reports = $accounts->map(function ($account) use ($movements, $opening, $closing, $calculator) {
            $balance = $opening['cash:'.$account->id] ?? '0';
            $previous = $balance;
            $transactions = $movements->where('bank_account_id', $account->id)->map(function ($tx) use (&$balance) {
                $balance = $tx->transaction_type === 'in' ? bcadd($balance, $tx->amount, 6) : bcsub($balance, $tx->amount, 6);

                return ['id' => $tx->id, 'source_id' => $tx->source_id, 'date' => $tx->date, 'type' => $tx->transaction_type,
                    'description' => $tx->description, 'amount' => $tx->amount, 'category' => $tx->category,
                    'created_by' => User::find($tx->created_by)?->name ?? 'System', 'running_balance' => $balance];
            })->values();
            $monthly = $transactions->groupBy(fn ($tx) => Carbon::parse($tx['date'])->format('Y-m'))->map(function ($rows, $month) use ($calculator) {
                $in = $calculator->sum($rows->where('type', 'in'), 'amount');
                $out = $calculator->sum($rows->where('type', 'out'), 'amount');

                return ['month' => Carbon::parse($month.'-01')->format('F Y'), 'inflows' => $in, 'outflows' => $out,
                    'net' => bcsub($in, $out, 6), 'transaction_count' => $rows->count()];
            })->values();

            return ['account' => ['id' => $account->id, 'name' => $account->account_name, 'bank' => $account->bank_name,
                'number' => $account->account_number, 'branch' => $account->branch_name],
                'opening_balance' => $previous, 'previous_balance' => $previous, 'current_balance' => $closing['cash:'.$account->id] ?? '0',
                'monthly_summary' => $monthly, 'transactions' => $transactions];
        });
        if (! $id) {
            $movements = $cash->consolidated($movements);
        }
        $purchases = $calculator->sum($movements->where('category', 'stock_purchase')->where('transaction_type', 'out'), 'amount');
        $refunds = $calculator->sum($movements->where('category', 'stock_refund')->where('transaction_type', 'in'), 'amount');

        return ['reports' => $reports, 'summary' => [
            'total_accounts' => $accounts->count(), 'total_balance' => $calculator->sum($reports, 'current_balance'),
            'total_inflows' => $calculator->sum($movements->where('transaction_type', 'in'), 'amount'),
            'total_outflows' => $calculator->sum($movements->where('transaction_type', 'out'), 'amount'),
            'total_transactions' => $movements->count(), 'total_sales_from_invoices' => $calculator->sum($movements->where('category', 'sale_collection')->where('transaction_type', 'in'), 'amount'),
            'total_product_purchases' => $purchases, 'total_product_refunds' => $refunds, 'net_product_amount' => bcsub($purchases, $refunds, 6),
        ]];
    }

    public function daily(int $year, int $month, ?int $id = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $cash = app(CashReport::class);
        $movements = $cash->movements($start->toDateString(), $end->toDateString(), $id);
        if (! $id) {
            $movements = $cash->consolidated($movements);
        }
        $movements = $movements->groupBy(fn ($tx) => $tx->date->toDateString());
        $opening = $cash->build($start->toDateString(), $end->toDateString(), $id)['receipt']['opening_cash_on_bank']['period'];
        $balance = $opening;
        $days = [];
        $zero = ['in' => ['fund' => '0', 'payment' => '0', 'extra' => '0', 'refund' => '0', 'other' => '0', 'total' => '0'],
            'out' => ['fund' => '0', 'purchase' => '0', 'supplier_payment' => '0', 'fixed_asset' => '0', 'expense' => '0', 'other' => '0', 'total' => '0']];
        $totals = $zero;
        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $row = $zero;
            foreach ($movements->get($date->toDateString(), collect()) as $tx) {
                $direction = $tx->transaction_type;
                $key = $direction === 'in' ? match ($tx->category) {
                    'fund_in' => 'fund', 'sale_collection' => 'payment', 'extra_income' => 'extra', 'stock_refund' => 'refund', default => 'other',
                } : match ($tx->category) {
                    'fund_out' => 'fund', 'stock_purchase' => 'purchase', 'supplier_payment' => 'supplier_payment',
                    'fixed_asset' => 'fixed_asset', 'expense' => 'expense', default => 'other',
                };
                $row[$direction][$key] = bcadd($row[$direction][$key], $tx->amount, 6);
                $row[$direction]['total'] = bcadd($row[$direction]['total'], $tx->amount, 6);
            }
            $balance = bcsub(bcadd($balance, $row['in']['total'], 6), $row['out']['total'], 6);
            $days[] = ['date' => $date->toDateString(), 'balance' => $balance] + $row;
            foreach (['in', 'out'] as $direction) {
                foreach ($row[$direction] as $key => $value) {
                    $totals[$direction][$key] = bcadd($totals[$direction][$key], $value, 6);
                }
            }
        }
        $accounts = BankAccount::withTrashed()->get();

        return ['bankAccounts' => $accounts, 'selectedAccount' => $id ? $accounts->firstWhere('id', $id) : null,
            'selectedBankAccount' => $id, 'selectedMonth' => $month, 'selectedYear' => $year,
            'previousMonthBalance' => $opening, 'dailyTransactions' => $days, 'monthTotals' => $totals,
            'currentAccountBalance' => $balance, 'filters' => ['year' => $year, 'month' => $month, 'bank_account_id' => $id]];
    }
}
