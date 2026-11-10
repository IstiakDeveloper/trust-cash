<?php

namespace App\Http\Controllers\Admin;

use App\Exports\IncomeExpenditureExport;
use App\Http\Controllers\Controller;
use App\Services\FinancialReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class IncomeExpenditureController extends Controller
{
    public function index(Request $request)
    {
        // Validate and set date range
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Set default to current month if no dates provided
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        // Calculate Income Section
        $incomeData = $this->calculateIncome($startDate, $endDate);

        // Calculate Expenditure Section
        $expenditureData = $this->calculateExpenditure($startDate, $endDate);

        // Prepare data for Inertia render
        $data = [
            'income' => $incomeData,
            'expenditure' => $expenditureData,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ];

        return Inertia::render('Admin/Reports/IncomeExpenditure', $data);
    }

    private function calculateIncome(Carbon $startDate, Carbon $endDate)
    {
        return app(FinancialReport::class)->income($startDate->toDateString(), $endDate->toDateString());
    }

    private function calculateExpenditure(Carbon $startDate, Carbon $endDate)
    {
        return app(FinancialReport::class)->expenditure($startDate->toDateString(), $endDate->toDateString());
    }

    public function downloadPdf(Request $request)
    {
        // Validate and set date range
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'selected_month' => 'nullable|integer|min:1|max:12',
            'selected_year' => 'nullable|integer',
        ]);

        // Parse the provided dates
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $month = $startDate->month;
        $year = $startDate->year;
        $monthName = $startDate->format('F');

        // Calculate Income Section
        $incomeData = $this->calculateIncome($startDate, $endDate);

        // Calculate Expenditure Section
        $expenditureData = $this->calculateExpenditure($startDate, $endDate);

        $locale = $request->input('locale', 'bn');
        $isBn = ($locale === 'bn');

        // Prepare data for PDF - using same structure as index, but with added month info
        $data = [
            'income' => $incomeData,
            'expenditure' => $expenditureData,
            'isBn' => $isBn,
            'locale' => $locale,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'year' => $isBn ? to_bangla_number($year) : $year,
                'month' => $month,
                'month_name' => $isBn ? to_bangla_month($month) : $monthName,
            ],
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.income-expenditure', $data);

        // Generate filename with month and year
        $filename = 'income-expenditure-'.$monthName.'-'.$year.'.pdf';

        // Download PDF
        return $pdf->download($filename);
    }

    public function downloadExcel(Request $request)
    {
        $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date']);
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        $incomeData = $this->calculateIncome($startDate, $endDate);
        $expenditureData = $this->calculateExpenditure($startDate, $endDate);

        $filename = 'income-expenditure-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.xlsx';

        return Excel::download(
            new IncomeExpenditureExport(
                $incomeData,
                $expenditureData,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ),
            $filename
        );
    }
}
