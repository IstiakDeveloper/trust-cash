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

class CustomerSalesExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $monthlySales;
    protected array $customer;
    protected float $previousBalance;
    protected array $monthlyTotals;
    protected string $periodLabel;

    public function __construct(array $monthlySales, array $customer = [], float $previousBalance = 0, array $monthlyTotals = [], string $periodLabel = '')
    {
        $this->monthlySales = $monthlySales;
        $this->customer = $customer;
        $this->previousBalance = $previousBalance;
        $this->monthlyTotals = $monthlyTotals;
        $this->periodLabel = $periodLabel;
    }

    public function title(): string
    {
        return 'Customer Ledger';
    }

    public function headings(): array
    {
        return [
            'SL',
            'Date',
            'Invoice No',
            'Purchased Items',
            'Total (BDT)',
            'Paid (BDT)',
            'Due (BDT)',
            'Status',
        ];
    }

    public function array(): array
    {
        $rows = [];

        // Meta header info
        $custName = $this->customer['name'] ?? 'Customer';
        $custPhone = $this->customer['phone'] ?? '—';
        $rows[] = ['INFO', "Customer: {$custName} ({$custPhone})", "Period: {$this->periodLabel}", '', '', '', '', ''];
        $rows[] = ['PREVIOUS', 'Previous Balance (পূর্বের বকেয়া)', '', '', '', '', $this->previousBalance, ''];
        $rows[] = ['', '', '', '', '', '', '', ''];

        $sl = 1;
        $totalSales = 0;
        $totalPaid = 0;
        $totalDue = 0;

        foreach ($this->monthlySales as $sale) {
            $total = (float) ($sale['total'] ?? 0);
            $paid = (float) ($sale['paid'] ?? 0);
            $due = (float) ($sale['due'] ?? 0);

            $totalSales += $total;
            $totalPaid += $paid;
            $totalDue += $due;

            $itemsSummary = [];
            if (!empty($sale['items'])) {
                foreach ($sale['items'] as $item) {
                    $itemsSummary[] = ($item['product'] ?? 'Item') . ' (' . ($item['quantity'] ?? 1) . ' @ ৳' . number_format((float) ($item['unit_price'] ?? 0), 2) . ')';
                }
            }

            $rows[] = [
                $sl++,
                $sale['date'] ?? '',
                $sale['invoice_no'] ?? '',
                implode(', ', $itemsSummary),
                $total,
                $paid,
                $due,
                $due > 0 ? 'DUE' : 'PAID',
            ];
        }

        // Totals row
        $rows[] = [
            'TOTAL',
            'Monthly Totals',
            '',
            'Total Invoices: ' . ($sl - 1),
            $totalSales,
            $totalPaid,
            $totalDue,
            '',
        ];

        // Net Due with Previous Balance
        $rows[] = [
            'NET DUE',
            'Net Customer Balance (পূর্বের বকেয়া সহ সর্বমোট দেনা)',
            '',
            '',
            '',
            '',
            $this->previousBalance + $totalDue,
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header style
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 900
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Numeric formats for columns E to G
        $sheet->getStyle("E2:G{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }
}
