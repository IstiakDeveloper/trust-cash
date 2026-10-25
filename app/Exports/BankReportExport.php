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

class BankReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $accounts;
    protected string $title;

    public function __construct(array $accounts, string $title = 'Bank Report')
    {
        $this->accounts = $accounts;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return [
            'SL',
            'Bank Name',
            'Account Name',
            'Account Number',
            'Branch',
            'Opening Balance (BDT)',
            'Total Inflow / Deposit (BDT)',
            'Total Outflow / Withdrawal (BDT)',
            'Current Balance (BDT)',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $sl = 1;

        $totalOpening = 0;
        $totalInflow = 0;
        $totalOutflow = 0;
        $totalBalance = 0;

        foreach ($this->accounts as $acc) {
            $opening = (float) ($acc['opening_balance'] ?? 0);
            $inflow = (float) ($acc['total_inflow'] ?? 0);
            $outflow = (float) ($acc['total_outflow'] ?? 0);
            $current = (float) ($acc['current_balance'] ?? 0);

            $totalOpening += $opening;
            $totalInflow += $inflow;
            $totalOutflow += $outflow;
            $totalBalance += $current;

            $rows[] = [
                $sl++,
                $acc['bank_name'] ?? '',
                $acc['account_name'] ?? '',
                $acc['account_number'] ?? '—',
                $acc['branch'] ?? '—',
                $opening,
                $inflow,
                $outflow,
                $current,
            ];
        }

        // Summary row
        $rows[] = [
            'TOTAL',
            'Total Accounts: ' . ($sl - 1),
            '',
            '',
            '',
            $totalOpening,
            $totalInflow,
            $totalOutflow,
            $totalBalance,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header style
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'], // Blue 700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Numeric columns F to I
        $sheet->getStyle("F2:I{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Total row style
        $sheet->getStyle("A{$totalRows}:I{$totalRows}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1E3A8A'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DBEAFE'], // Blue 100
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ]);

        return [];
    }
}
