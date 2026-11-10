<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomerSalesExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\FinancialLedger;
use App\Services\SalesReporting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CustomerSalesReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Reports/CustomerSales/Index', [
            'customers' => Customer::withTrashed()->orderBy('name')->get(['id', 'name', 'phone']),
            'months' => collect(range(1, 12))->map(fn ($month) => ['value' => $month, 'label' => Carbon::create()->month($month)->format('F')]),
            'years' => collect(range(now()->year - 4, now()->year))->map(fn ($year) => ['value' => $year, 'label' => $year]),
        ]);
    }

    private function data(Request $request): array
    {
        $request->validate(['customer_id' => 'required|exists:customers,id', 'year' => 'required|integer|min:1900|max:9999', 'month' => 'required|integer|between:1,12']);
        $customer = Customer::withTrashed()->findOrFail($request->integer('customer_id'));
        $start = Carbon::create($request->integer('year'), $request->integer('month'), 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $report = app(SalesReporting::class)->build($start->toDateString(), $end->toDateString(), $customer->id);
        $opening = app(FinancialLedger::class)->balances($start->copy()->subDay()->toDateString())['receivable:'.$customer->id] ?? '0';
        $closing = app(FinancialLedger::class)->balances($end->toDateString())['receivable:'.$customer->id] ?? '0';

        return ['customer' => $customer, 'previousBalance' => $opening, 'monthlySales' => $report['rows'],
            'monthlyTotals' => ['total_sales' => $report['summary']['total_amount'], 'total_paid' => $report['summary']['received'],
                'total_due' => $closing, 'due_movement' => $report['summary']['due']], 'periodLabel' => $start->format('F Y')];
    }

    public function getReportData(Request $request)
    {
        return response()->json($this->data($request));
    }

    public function downloadExcel(Request $request)
    {
        $data = $this->data($request);

        return Excel::download(new CustomerSalesExport($data['monthlySales']->toArray(), $data['customer']->toArray(),
            (float) $data['previousBalance'], $data['monthlyTotals'], $data['periodLabel']), 'customer-ledger.xlsx');
    }
}
