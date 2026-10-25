<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomeExpenditureExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $income;
    protected array $expenditure;
    protected string $startDate;
    protected string $endDate;

    public function __construct(array $income, array $expenditure, string $startDate = '', string $endDate = '')
    {
        $this->income = $income;
        $this->expenditure = $expenditure;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Income & Expenditure';
    }

    public function headings(): array
    {
        return [
            'Section',
            'Particulars / Account Head',
            'Period Amount (BDT)',
            'Cumulative Amount (BDT)',
        ];
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['INFO', 'Statement of Income & Expenditure', 'From: ' . $this->startDate, 'To: ' . $this->endDate];
        $rows[] = ['', '', '', ''];

        // --- INCOME SECTION ---
        $rows[] = ['INCOME', '[1. INCOMES (আয়সমূহ)]', '', ''];

        // Sales Profit
        $salesProfitPeriod = (float) ($this->income['sales_profit']['period'] ?? 0);
        $salesProfitCumulative = (float) ($this->income['sales_profit']['cumulative'] ?? 0);
        $rows[] = ['INCOME', 'Sales Profit (পণ্য বিক্রয়জনিত মোট লাভ)', $salesProfitPeriod, $salesProfitCumulative];

        // Extra Incomes
        $extraCats = $this->income['extra_income']['categories'] ?? [];
        foreach ($extraCats as $cat) {
            $rows[] = [
                'INCOME',
                '  • Extra Income: ' . ($cat['name'] ?? 'Other'),
                (float) ($cat['period'] ?? 0),
                (float) ($cat['cumulative'] ?? 0),
            ];
        }

        $totalIncomePeriod = (float) ($this->income['total']['period'] ?? 0);
        $totalIncomeCumulative = (float) ($this->income['total']['cumulative'] ?? 0);
        $rows[] = ['INCOME TOTAL', 'TOTAL INCOME (সর্বমোট আয়)', $totalIncomePeriod, $totalIncomeCumulative];

        $rows[] = ['', '', '', ''];

        // --- EXPENDITURE SECTION ---
        $rows[] = ['EXPENDITURE', '[2. EXPENDITURES (ব্যয়সমূহ)]', '', ''];

        $expenseCats = $this->expenditure['expenses']['categories'] ?? [];
        foreach ($expenseCats as $cat) {
            $rows[] = [
                'EXPENDITURE',
                '  • ' . ($cat['name'] ?? 'Operating Expense'),
                (float) ($cat['period'] ?? 0),
                (float) ($cat['cumulative'] ?? 0),
            ];
        }

        $totalExpPeriod = (float) ($this->expenditure['total']['period'] ?? 0);
        $totalExpCumulative = (float) ($this->expenditure['total']['cumulative'] ?? 0);
        $rows[] = ['EXPENDITURE TOTAL', 'TOTAL EXPENDITURE (সর্বমোট পরিচালন ব্যয়)', $totalExpPeriod, $totalExpCumulative];

        $rows[] = ['', '', '', ''];

        // --- NET PROFIT / SURPLUS ---
        $netPeriod = $totalIncomePeriod - $totalExpPeriod;
        $netCumulative = $totalIncomeCumulative - $totalExpCumulative;

        $rows[] = [
            'NET RESULT',
            'NET PROFIT / SURPLUS (খাত অনুযায়ী নীট লাভ / উদ্বৃত্ত)',
            $netPeriod,
            $netCumulative,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4338CA'], // Indigo 700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Numeric formats for columns C and D
        $sheet->getStyle("C2:D{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }
}
