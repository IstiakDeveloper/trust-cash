<?php

namespace App\Services;

use App\Models\BankAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashReport
{
    public function build(string $start, string $end, ?int $accountId = null): array
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('End date must follow start date.');
        }
        $accounts = BankAccount::withTrashed()->when($accountId, fn ($q) => $q->whereKey($accountId))->get();
        if ($accountId && $accounts->isEmpty()) {
            abort(404);
        }
        if (! DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
            throw ValidationException::withMessages(['accounting' => 'Historical cash accounting must be reconciled before reporting.']);
        }
        $transactions = $this->movements('1000-01-01', $end, $accountId);
        if (! $accountId) {
            $transactions = $this->consolidated($transactions);
        }
        $prior = app(FinancialLedger::class)->balances(Carbon::parse($start)->subDay()->toDateString());
        $openingPeriod = '0';
        $openingCumulative = '0';
        foreach ($accounts as $account) {
            $openingPeriod = bcadd($openingPeriod, $prior['cash:'.$account->id] ?? '0', 6);
        }
        $receipt = ['opening_cash_on_bank' => ['period' => $openingPeriod, 'cumulative' => $openingCumulative]];
        $payment = [];
        $receiptRows = [];
        $paymentRows = [];
        foreach ($transactions as $tx) {
            $key = $tx->category ?: 'other';
            $rows = $tx->transaction_type === 'in' ? $receiptRows : $paymentRows;
            $rows[$key] ??= ['name' => ucwords(str_replace('_', ' ', $key)), 'period' => '0', 'cumulative' => '0'];
            $rows[$key]['cumulative'] = bcadd($rows[$key]['cumulative'], (string) $tx->amount, 6);
            if ($tx->date->toDateString() >= $start) {
                $rows[$key]['period'] = bcadd($rows[$key]['period'], (string) $tx->amount, 6);
            }
            if ($tx->transaction_type === 'in') {
                $receiptRows = $rows;
            } else {
                $paymentRows = $rows;
            }
        }
        $sum = app(InventoryReport::class);
        $in = ['period' => $sum->sum($receiptRows, 'period'), 'cumulative' => $sum->sum($receiptRows, 'cumulative')];
        $out = ['period' => $sum->sum($paymentRows, 'period'), 'cumulative' => $sum->sum($paymentRows, 'cumulative')];
        $closing = bcsub(bcadd($openingPeriod, $in['period'], 6), $out['period'], 6);
        $receipt['rows'] = array_values($receiptRows);
        $receipt['total'] = ['period' => bcadd($openingPeriod, $in['period'], 6), 'cumulative' => bcadd($openingCumulative, $in['cumulative'], 6)];
        $payment['rows'] = array_values($paymentRows);
        $payment['closing_cash_at_bank'] = ['period' => $closing, 'cumulative' => $closing];
        $payment['total'] = ['period' => bcadd($out['period'], $closing, 6), 'cumulative' => bcadd($out['cumulative'], $closing, 6)];

        return ['receipt' => $receipt, 'payment' => $payment, 'reconciliation_difference' => bcsub($receipt['total']['period'], $payment['total']['period'], 6)];
    }

    public function movements(string $start, string $end, ?int $accountId = null, bool $includeOpenings = true)
    {
        if (! DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
            throw ValidationException::withMessages(['accounting' => 'Reconcile historical cash accounting before reporting.']);
        }
        $accounts = BankAccount::withTrashed()->when($accountId, fn ($q) => $q->whereKey($accountId))->pluck('id');

        return DB::table('ledger_lines as line')->join('ledger_documents as doc', 'doc.id', '=', 'line.document_id')
            ->leftJoin('bank_transactions as tx', fn ($join) => $join->on('tx.id', '=', 'doc.source_id')->where('doc.source_type', 'bank_transactions'))
            ->whereIn('doc.source_type', $includeOpenings ? ['bank_transactions', 'bank_accounts'] : ['bank_transactions'])
            ->whereIn('line.account', $accounts->map(fn ($id) => 'cash:'.$id))->where('line.amount', '!=', 0)->whereBetween('line.date', [$start, $end])
            ->orderBy('line.date')->orderBy('line.id')->get([
                'line.id', 'line.date', 'line.account', 'line.amount', 'line.category', 'line.reversal',
                'doc.source_type', 'doc.source_id', 'tx.transfer_group', 'tx.description', 'tx.created_by',
            ])->map(function ($line) {
                $line->bank_account_id = (int) substr($line->account, 5);
                $line->transaction_type = bccomp((string) $line->amount, '0', 6) >= 0 ? 'in' : 'out';
                $line->amount = ltrim((string) $line->amount, '-');
                $line->date = Carbon::parse($line->date);
                if ($line->source_type === 'bank_accounts') {
                    $line->category = 'account_opening';
                    $line->description = 'Account opening balance';
                }
                if ($line->reversal) {
                    $line->description = 'Reversal: '.$line->description;
                }

                return $line;
            });
    }

    public function consolidated($movements)
    {
        return $movements->reject(function ($tx) use ($movements) {
            if ($tx->category !== 'transfer' || ! $tx->transfer_group) {
                return false;
            }
            $legs = $movements->where('transfer_group', $tx->transfer_group);
            $calculator = app(InventoryReport::class);

            return $legs->count() === 2 && $legs->pluck('bank_account_id')->unique()->count() === 2
                && $legs->pluck('date')->map(fn ($date) => $date->toDateString())->unique()->count() === 1
                && bccomp($calculator->sum($legs->where('transaction_type', 'in'), 'amount'), $calculator->sum($legs->where('transaction_type', 'out'), 'amount'), 6) === 0;
        })->values();
    }

    public function balance(string $date): string
    {
        return $this->build('1000-01-01', $date)['payment']['closing_cash_at_bank']['period'];
    }
}
