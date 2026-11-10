<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BankTransactionReportExport;
use App\Http\Controllers\Controller;
use App\Services\BankReporting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BankTransactionReportController extends Controller
{
    private function data(Request $request): array
    {
        $request->validate(['year' => 'nullable|integer|min:1900|max:9999', 'month' => 'nullable|integer|between:1,12', 'bank_account_id' => 'nullable|string']);
        $id = $request->input('bank_account_id');
        if ($id !== null && $id !== '' && $id !== 'all' && (! ctype_digit((string) $id) || (int) $id < 1)) {
            throw ValidationException::withMessages(['bank_account_id' => 'Select a valid account.']);
        }

        return app(BankReporting::class)->daily($request->integer('year', now()->year), $request->integer('month', now()->month), $id && $id !== 'all' ? (int) $id : null);
    }

    public function index(Request $request)
    {
        return Inertia::render('Admin/Reports/BankTransactionReport', $this->data($request));
    }

    public function downloadPdf(Request $request)
    {
        return Pdf::loadView('pdf.bank-transaction-report', $this->data($request) + ['isBn' => $request->input('locale', 'bn') === 'bn'])
            ->setPaper('a4', 'landscape')->download('bank-transactions.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $data = $this->data($request);

        return Excel::download(new BankTransactionReportExport($data['dailyTransactions'], (float) $data['previousMonthBalance']), 'bank-transactions.xlsx');
    }
}
