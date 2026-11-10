<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReporting
{
    public function build(string $start, string $end, ?int $customerId = null, ?int $bankId = null, ?string $status = null): array
    {
        app(FinancialLedger::class)->balances($end);
        if ($start > $end) {
            throw new \InvalidArgumentException('End date must follow start date.');
        }
        $entries = DB::table('sale_accounting_entries')->where('date', '<=', $end)
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($bankId, fn ($q) => $q->where('bank_account_id', $bankId))->orderBy('date')->orderBy('id')->get();
        $payments = $this->payments($end, $customerId, $bankId);
        $invoices = Sale::withTrashed()->get()->keyBy('id');
        $groups = [];
        foreach ($entries->filter(fn ($entry) => $entry->date >= $start) as $entry) {
            $key = $entry->sale_id.'|'.$entry->date.'|'.($entry->customer_id ?? 0);
            $groups[$key] ??= ['sale_id' => $entry->sale_id, 'date' => $entry->date, 'customer_id' => $entry->customer_id, 'entries' => [], 'payments' => []];
            $groups[$key]['entries'][] = $entry;
        }
        foreach ($payments->filter(fn ($payment) => $payment['date'] >= $start) as $payment) {
            $key = $payment['sale_id'].'|'.$payment['date'].'|'.($payment['customer_id'] ?? 0);
            $groups[$key] ??= ['sale_id' => $payment['sale_id'], 'date' => $payment['date'], 'customer_id' => $payment['customer_id'], 'entries' => [], 'payments' => []];
            $groups[$key]['payments'][] = $payment;
        }
        $calculator = app(InventoryReport::class);
        $rows = collect($groups)->map(function ($group, $key) use ($entries, $payments, $invoices, $calculator) {
            $sale = $invoices->get($group['sale_id']);
            $gross = $calculator->sum($group['entries'], 'gross');
            $discount = $calculator->sum($group['entries'], 'discount');
            $tax = $calculator->sum($group['entries'], 'tax');
            $total = bcadd($calculator->sum($group['entries'], 'net'), $tax, 6);
            $paid = $calculator->sum($group['payments'], 'amount');
            $allEntries = $entries->where('sale_id', $group['sale_id'])->where('customer_id', $group['customer_id']);
            $allPayments = $payments->where('sale_id', $group['sale_id'])->where('customer_id', $group['customer_id']);
            $invoicePaid = $calculator->sum($allPayments, 'amount');
            $invoiceDue = bcsub(bcadd($calculator->sum($allEntries, 'net'), $calculator->sum($allEntries, 'tax'), 6), $invoicePaid, 6);
            $invoiceStatus = bccomp($invoiceDue, '0', 2) <= 0 ? 'paid' : (bccomp($invoicePaid, '0', 2) > 0 ? 'partial' : 'due');
            $items = collect($group['entries'])->map(fn ($entry) => [
                'product' => Product::withTrashed()->find($entry->product_id)?->name ?? 'Archived product',
                'quantity' => $entry->quantity, 'unit_price' => bccomp((string) $entry->quantity, '0', 6) === 0 ? '0' : bcdiv((string) $entry->gross, (string) $entry->quantity, 6),
                'subtotal' => $entry->net, 'type' => $entry->source_type,
            ])->values()->all();

            return ['id' => $key, 'sale_id' => $group['sale_id'], 'date' => $group['date'], 'created_at' => '',
                'invoice_no' => $sale?->invoice_no ?? 'Archived invoice', 'customer_id' => $group['customer_id'],
                'customer' => Customer::withTrashed()->find($group['customer_id'])?->name ?? 'Walk-in Customer',
                'subtotal' => $gross, 'discount' => $discount, 'tax' => $tax, 'total' => $total, 'paid' => $paid,
                'due' => bcsub($total, $paid, 6), 'invoice_due' => $invoiceDue, 'payment_status' => $invoiceStatus,
                'items' => $items, 'payments' => $group['payments']];
        })->when($status, fn ($rows) => $rows->where('payment_status', $status))->sortBy('date')->values();
        $summary = $this->summary($rows);
        $monthly = $rows->groupBy(fn ($row) => substr($row['date'], 0, 7))->map(function ($monthRows, $month) {
            $daily = $monthRows->groupBy('date')->map(fn ($dayRows, $date) => ['date' => $date, 'sales' => $dayRows->values(), 'summary' => $this->summary($dayRows)])->values();

            return ['month' => Carbon::parse($month.'-01')->format('F Y'), 'daily_data' => $daily, 'daily_sales' => $daily, 'summary' => $this->summary($monthRows)];
        })->values();

        return ['rows' => $rows, 'monthly_reports' => $monthly, 'summary' => $summary];
    }

    private function summary($rows): array
    {
        $calculator = app(InventoryReport::class);
        $methods = $rows->flatMap(fn ($row) => $row['payments'])->groupBy('method')->map(fn ($payments) => [
            'amount' => $calculator->sum($payments, 'amount'),
            'bank_details' => $payments->groupBy('bank_account_id')->map(fn ($bankPayments) => [
                'bank_name' => $bankPayments->first()['bank_name'], 'account_number' => $bankPayments->first()['account_number'],
                'amount' => $calculator->sum($bankPayments, 'amount'),
            ]),
        ]);

        return ['total_sales' => $rows->pluck('sale_id')->unique()->count(), 'total_amount' => $calculator->sum($rows, 'total'),
            'subtotal' => $calculator->sum($rows, 'subtotal'), 'discount' => $calculator->sum($rows, 'discount'), 'tax' => $calculator->sum($rows, 'tax'),
            'received' => $calculator->sum($rows, 'paid'), 'due' => $calculator->sum($rows, 'due'), 'payment_methods' => $methods];
    }

    private function payments(string $end, ?int $customerId, ?int $bankId)
    {
        $later = DB::table('sale_payments')->pluck('sale_id', 'id');
        $returns = DB::table('sale_returns')->pluck('sale_id', 'id');

        return DB::table('ledger_lines as line')->join('ledger_documents as doc', 'doc.id', '=', 'line.document_id')
            ->whereIn('doc.source_type', ['sales', 'sale_payments', 'sale_return_refund'])->where('line.account', 'like', 'receivable:%')
            ->where('line.amount', '!=', 0)->where('line.date', '<=', $end)
            ->when($customerId, fn ($q) => $q->where('line.account', 'receivable:'.$customerId))
            ->when($bankId, fn ($q) => $q->where('line.bank_account_id', $bankId))
            ->orderBy('line.date')->orderBy('line.id')->get(['line.date', 'line.account', 'line.amount', 'line.bank_account_id', 'doc.source_type', 'doc.source_id'])
            ->map(function ($line) use ($later, $returns) {
                $saleId = match ($line->source_type) {
                    'sales' => $line->source_id, 'sale_payments' => $later->get($line->source_id), default => $returns->get($line->source_id)
                };
                $bank = BankAccount::withTrashed()->find($line->bank_account_id);

                return ['sale_id' => $saleId, 'customer_id' => ((int) substr($line->account, 11)) ?: null, 'date' => $line->date,
                    'amount' => bcmul((string) $line->amount, '-1', 6), 'method' => strtolower($bank?->bank_name ?? '') === 'cash' ? 'cash' : 'bank', 'bank_name' => $bank?->bank_name,
                    'bank_account_id' => $line->bank_account_id,
                    'account_number' => $bank?->account_number, 'transaction_id' => $line->source_type.'-'.$line->source_id];
            });
    }
}
