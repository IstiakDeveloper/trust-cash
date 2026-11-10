<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BankReportExport;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\BankReporting;
use App\Services\CashReport;
use App\Services\InventoryReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BankReportController extends Controller
{
    private function getTransactionEffect($type, $amount)
    {
        return match ($type) {
            'in' => $amount,
            'out' => -$amount,
            default => 0,
        };
    }

    private function productMovements(int $productId, string $through)
    {
        return app(CashReport::class)->movements('1000-01-01', $through)->filter(function ($tx) use ($productId) {
            if (! in_array($tx->category, ['stock_purchase', 'stock_refund'], true)) {
                return false;
            }
            if (preg_match('/product ID:\s*(\d+)\b/', $tx->description ?? '', $match)) {
                return (int) $match[1] === $productId;
            }
            if (preg_match('/stock lot #(\d+)\b/', $tx->description ?? '', $match)) {
                return ProductStock::withTrashed()->whereKey($match[1])->where('product_id', $productId)->exists();
            }

            return false;
        });
    }

    public function getProductPurchaseTotal($productId)
    {
        $rows = $this->productMovements((int) $productId, now()->toDateString());
        $sum = app(InventoryReport::class);

        return bcsub($sum->sum($rows->where('transaction_type', 'out'), 'amount'), $sum->sum($rows->where('transaction_type', 'in'), 'amount'), 6);
    }

    public function productTransactions(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id', 'from_date' => 'nullable|date', 'to_date' => 'nullable|date|after_or_equal:from_date']);
        $start = $request->filled('from_date') ? Carbon::parse($request->from_date)->toDateString() : now()->startOfMonth()->toDateString();
        $end = $request->filled('to_date') ? Carbon::parse($request->to_date)->toDateString() : now()->endOfMonth()->toDateString();
        $history = $this->productMovements($request->integer('product_id'), $end);
        $total = '0';
        $transactions = [];
        foreach ($history as $tx) {
            $total = $tx->transaction_type === 'out' ? bcadd($total, $tx->amount, 6) : bcsub($total, $tx->amount, 6);
            if ($tx->date->toDateString() >= $start) {
                $transactions[] = ['id' => $tx->id, 'date' => $tx->date,
                    'type' => $tx->transaction_type, 'description' => $tx->description, 'amount' => $tx->amount,
                    'running_total' => $total, 'created_by' => User::find($tx->created_by)?->name ?? 'System'];
            }
        }
        $period = $history->filter(fn ($tx) => $tx->date->toDateString() >= $start);
        $sum = app(InventoryReport::class);
        $out = $sum->sum($period->where('transaction_type', 'out'), 'amount');
        $in = $sum->sum($period->where('transaction_type', 'in'), 'amount');

        return Inertia::render('Admin/Reports/ProductTransactions', ['product_id' => $request->integer('product_id'),
            'filters' => ['from_date' => $start, 'to_date' => $end], 'transactions' => $transactions,
            'summary' => ['total_purchases' => $out, 'total_refunds' => $in, 'net_amount' => bcsub($out, $in, 6)]]);
    }

    public function getTotalSalesFromInvoices($fromDate, $toDate)
    {
        return app(InventoryReport::class)->sum(app(CashReport::class)->movements(
            Carbon::parse($fromDate)->toDateString(), Carbon::parse($toDate)->toDateString())->where('category', 'sale_collection')->where('transaction_type', 'in'), 'amount');
    }

    private function data(Request $request): array
    {
        $request->validate(['account_id' => 'nullable|exists:bank_accounts,id', 'from_date' => 'nullable|date', 'to_date' => 'nullable|date|after_or_equal:from_date']);
        $start = $request->filled('from_date') ? Carbon::parse($request->from_date)->toDateString() : now()->startOfMonth()->toDateString();
        $end = $request->filled('to_date') ? Carbon::parse($request->to_date)->toDateString() : now()->endOfMonth()->toDateString();
        if ($start > $end) {
            throw ValidationException::withMessages(['to_date' => 'End date must follow start date.']);
        }

        return app(BankReporting::class)->accounts($start, $end, $request->filled('account_id') ? $request->integer('account_id') : null) + [
            'bankAccounts' => BankAccount::withTrashed()->get(),
            'filters' => ['account_id' => $request->input('account_id'), 'from_date' => $start, 'to_date' => $end],
        ];
    }

    public function index(Request $request)
    {
        return Inertia::render('Admin/Reports/BankReport', $this->data($request));
    }

    public function downloadPdf(Request $request)
    {
        return Pdf::loadView('reports.bank-report-pdf', $this->data($request) + ['isBn' => $request->input('locale', 'bn') === 'bn'])
            ->setPaper('a4', 'landscape')->download('bank-report.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $data = $this->data($request);
        $rows = $data['reports']->map(fn ($report) => [
            'bank_name' => $report['account']['bank'], 'account_name' => $report['account']['name'],
            'account_number' => $report['account']['number'], 'branch' => $report['account']['branch'],
            'opening_balance' => $report['previous_balance'],
            'total_inflow' => $report['transactions']->where('type', 'in')->sum('amount'),
            'total_outflow' => $report['transactions']->where('type', 'out')->sum('amount'), 'current_balance' => $report['current_balance'],
        ])->all();

        return Excel::download(new BankReportExport($rows), 'bank-report.xlsx');
    }
}
