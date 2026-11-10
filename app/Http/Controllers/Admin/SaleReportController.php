<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SaleReportExport;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Services\SalesReporting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SaleReportController extends Controller
{
    private function data(Request $request): array
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id', 'payment_status' => 'nullable|in:paid,partial,due',
            'bank_account_id' => 'nullable|exists:bank_accounts,id', 'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);
        $start = $request->filled('from_date') ? Carbon::parse($request->from_date)->toDateString() : now()->startOfMonth()->toDateString();
        $end = $request->filled('to_date') ? Carbon::parse($request->to_date)->toDateString() : now()->endOfMonth()->toDateString();

        return app(SalesReporting::class)->build($start, $end, $request->filled('customer_id') ? $request->integer('customer_id') : null,
            $request->filled('bank_account_id') ? $request->integer('bank_account_id') : null, $request->input('payment_status')) + [
                'customers' => Customer::withTrashed()->get(['id', 'name', 'phone']), 'bankAccounts' => BankAccount::withTrashed()->get(),
                'filters' => ['from_date' => $start, 'to_date' => $end] + $request->only(['customer_id', 'bank_account_id', 'payment_status']),
            ];
    }

    public function index(Request $request)
    {
        $data = $this->data($request);

        return Inertia::render('Admin/Reports/SalesReport', $data + ['reports' => $data['monthly_reports']]);
    }

    public function downloadPdf(Request $request)
    {
        return Pdf::loadView('reports.sales-report-pdf', $this->data($request) + ['isBn' => $request->input('locale', 'bn') === 'bn'])
            ->setPaper('a4', 'landscape')->download('sales-report.pdf');
    }

    public function downloadExcel(Request $request)
    {
        return Excel::download(new SaleReportExport($this->data($request)), 'sales-report.xlsx');
    }
}
