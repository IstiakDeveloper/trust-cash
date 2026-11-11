<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BalanceSheetExport;
use App\Http\Controllers\Controller;
use App\Services\FinancialReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BalanceSheetController extends Controller
{
    private function data(Request $request): array
    {
        $request->validate(['end_date' => 'nullable|date']);
        $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->toDateString() : now()->toDateString();
        $start = Carbon::parse($end)->startOfMonth()->toDateString();

        return app(FinancialReport::class)->balanceSheet($end) + [
            'filters' => ['start_date' => $start, 'end_date' => $end], 'start_date' => $start, 'end_date' => $end,
        ];
    }

    public function index(Request $request)
    {
        return Inertia::render('Admin/Reports/BalanceSheet', $this->data($request));
    }

    public function downloadPdf(Request $request)
    {
        $data = $this->data($request);

        return Pdf::loadView('pdf.balance-sheet', $data + ['isBn' => $request->input('locale', 'bn') === 'bn'])
            ->download('balance-sheet-'.$data['end_date'].'.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $data = $this->data($request);

        return Excel::download(new BalanceSheetExport($data['fund_and_liabilities'], $data['property_and_assets'],
            $data['start_date'], $data['end_date'], $data['reconciliation'], $data['legacy_carry_forwards']), 'balance-sheet-'.$data['end_date'].'.xlsx');
    }
}
