<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptPaymentExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $receipt;

    protected array $payment;

    protected ?string $accountName;

    protected string $startDate;

    protected string $endDate;

    public function __construct(array $receipt, array $payment, ?string $accountName = null, string $startDate = '', string $endDate = '')
    {
        $this->receipt = $receipt;
        $this->payment = $payment;
        $this->accountName = $accountName ?? 'All Bank Accounts (Consolidated)';
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Receipt & Payment';
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
        if (isset($this->receipt['rows'])) {
            $rows = [['INFO', $this->accountName, $this->startDate, $this->endDate]];
            $rows[] = ['RECEIPT', 'Opening cash and bank', (float) $this->receipt['opening_cash_on_bank']['period'], (float) $this->receipt['opening_cash_on_bank']['cumulative']];
            foreach ($this->receipt['rows'] as $row) {
                $rows[] = ['RECEIPT', $row['name'], (float) $row['period'], (float) $row['cumulative']];
            }
            $rows[] = ['RECEIPT TOTAL', 'Total', (float) $this->receipt['total']['period'], (float) $this->receipt['total']['cumulative']];
            foreach ($this->payment['rows'] as $row) {
                $rows[] = ['PAYMENT', $row['name'], (float) $row['period'], (float) $row['cumulative']];
            }
            $rows[] = ['PAYMENT', 'Closing cash and bank', (float) $this->payment['closing_cash_at_bank']['period'], (float) $this->payment['closing_cash_at_bank']['cumulative']];
            $rows[] = ['PAYMENT TOTAL', 'Total', (float) $this->payment['total']['period'], (float) $this->payment['total']['cumulative']];

            return $rows;
        }
        $rows = [];

        // Meta info row
        $rows[] = ['INFO', 'Account: '.$this->accountName, 'From: '.$this->startDate, 'To: '.$this->endDate];
        $rows[] = ['', '', '', ''];

        // --- RECEIPTS SECTION ---
        $rows[] = ['RECEIPT', '[1. RECEIPTS (ইনফ্লো / প্রাপ্তি)]', '', ''];

        // Opening Balance
        $rows[] = [
            'RECEIPT',
            'Opening Cash at Bank',
            (float) ($this->receipt['opening_cash_at_bank']['period'] ?? 0),
            (float) ($this->receipt['opening_cash_at_bank']['cumulative'] ?? 0),
        ];

        // Sales Collection
        $rows[] = [
            'RECEIPT',
            'Sales Collection (বিক্রয় বাবদ নগদ/ব্যাংক প্রাপ্তি)',
            (float) ($this->receipt['sales_collection']['period'] ?? 0),
            (float) ($this->receipt['sales_collection']['cumulative'] ?? 0),
        ];

        // Extra Incomes
        $extraCats = $this->receipt['extra_incomes']['categories'] ?? [];
        if (! empty($extraCats)) {
            foreach ($extraCats as $cat) {
                $rows[] = [
                    'RECEIPT',
                    '  • Extra Income: '.($cat['name'] ?? 'General'),
                    (float) ($cat['period'] ?? 0),
                    (float) ($cat['cumulative'] ?? 0),
                ];
            }
        }
        $rows[] = [
            'RECEIPT',
            'Total Extra Income (অতিরিক্ত আয়)',
            (float) ($this->receipt['extra_incomes']['total']['period'] ?? 0),
            (float) ($this->receipt['extra_incomes']['total']['cumulative'] ?? 0),
        ];

        // Fund In
        $fundIns = $this->receipt['fund_in']['items'] ?? [];
        if (! empty($fundIns)) {
            foreach ($fundIns as $item) {
                $rows[] = [
                    'RECEIPT',
                    '  • Fund Receipt: '.($item['name'] ?? 'Capital'),
                    (float) ($item['period'] ?? 0),
                    (float) ($item['cumulative'] ?? 0),
                ];
            }
        }
        $rows[] = [
            'RECEIPT',
            'Total Fund In (তহবিল / মূলধন প্রাপ্তি)',
            (float) ($this->receipt['fund_in']['total']['period'] ?? 0),
            (float) ($this->receipt['fund_in']['total']['cumulative'] ?? 0),
        ];

        // Total Receipts
        $rows[] = [
            'RECEIPT TOTAL',
            'TOTAL RECEIPTS (সর্বমোট প্রাপ্তি)',
            (float) ($this->receipt['total']['period'] ?? 0),
            (float) ($this->receipt['total']['cumulative'] ?? 0),
        ];

        $rows[] = ['', '', '', ''];

        // --- PAYMENTS SECTION ---
        $rows[] = ['PAYMENT', '[2. PAYMENTS (আউটফ্লো / পরিশোধ)]', '', ''];

        // Supplier Payments
        $rows[] = [
            'PAYMENT',
            'Supplier Payments (পণ্য ক্রয় বাবদ সরবরাহকারীকে পরিশোধ)',
            (float) ($this->payment['supplier_payments']['period'] ?? 0),
            (float) ($this->payment['supplier_payments']['cumulative'] ?? 0),
        ];

        // Fixed Assets
        $assets = $this->payment['fixed_assets']['items'] ?? [];
        if (! empty($assets)) {
            foreach ($assets as $asset) {
                $rows[] = [
                    'PAYMENT',
                    '  • Fixed Asset: '.($asset['name'] ?? 'Asset'),
                    (float) ($asset['period'] ?? 0),
                    (float) ($asset['cumulative'] ?? 0),
                ];
            }
        }
        $rows[] = [
            'PAYMENT',
            'Total Fixed Assets (স্থায়ী সম্পদ ক্রয়)',
            (float) ($this->payment['fixed_assets']['total']['period'] ?? 0),
            (float) ($this->payment['fixed_assets']['total']['cumulative'] ?? 0),
        ];

        // Fund Refund
        $fundRefunds = $this->payment['fund_refund']['items'] ?? [];
        if (! empty($fundRefunds)) {
            foreach ($fundRefunds as $fRef) {
                $rows[] = [
                    'PAYMENT',
                    '  • Fund Refund: '.($fRef['name'] ?? 'Refund'),
                    (float) ($fRef['period'] ?? 0),
                    (float) ($fRef['cumulative'] ?? 0),
                ];
            }
        }
        $rows[] = [
            'PAYMENT',
            'Total Fund Refund (তহবিল ফেরত)',
            (float) ($this->payment['fund_refund']['total']['period'] ?? 0),
            (float) ($this->payment['fund_refund']['total']['cumulative'] ?? 0),
        ];

        // Expenses
        $expenseCats = $this->payment['expenses']['categories'] ?? [];
        if (! empty($expenseCats)) {
            foreach ($expenseCats as $expCat) {
                $rows[] = [
                    'PAYMENT',
                    '  • Expense: '.($expCat['name'] ?? 'General'),
                    (float) ($expCat['period'] ?? 0),
                    (float) ($expCat['cumulative'] ?? 0),
                ];
            }
        }
        $rows[] = [
            'PAYMENT',
            'Total Operating Expenses (মোট পরিচালন ব্যয়)',
            (float) ($this->payment['expenses']['total']['period'] ?? 0),
            (float) ($this->payment['expenses']['total']['cumulative'] ?? 0),
        ];

        // Closing Balance
        $rows[] = [
            'PAYMENT',
            'Closing Cash at Bank (সমাপনী ব্যাংক ব্যালেন্স)',
            (float) ($this->payment['closing_cash_at_bank']['period'] ?? 0),
            (float) ($this->payment['closing_cash_at_bank']['cumulative'] ?? 0),
        ];

        // Total Payments
        $rows[] = [
            'PAYMENT TOTAL',
            'TOTAL PAYMENTS (সর্বমোট পরিশোধ)',
            (float) ($this->payment['total']['period'] ?? 0),
            (float) ($this->payment['total']['cumulative'] ?? 0),
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header Style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '047857'], // Emerald 700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Numeric Formats for columns C & D
        $sheet->getStyle("C2:D{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }
}
