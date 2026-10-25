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

class SaleReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $data;
    protected string $title;

    public function __construct(array $data, string $title = 'Sales Report')
    {
        $this->data = $data;
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
            'Date',
            'Time',
            'Invoice No',
            'Customer',
            'Total (BDT)',
            'Paid (BDT)',
            'Due (BDT)',
            'Status',
            'Payment Methods & Bank Details',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $sl = 1;
        $totalAmount = 0;
        $totalPaid = 0;
        $totalDue = 0;

        $monthlyReports = $this->data['monthly_reports'] ?? [];
        if (!is_array($monthlyReports) && method_exists($monthlyReports, 'toArray')) {
            $monthlyReports = $monthlyReports->toArray();
        }

        foreach ($monthlyReports as $monthReport) {
            $dailyData = $monthReport['daily_sales'] ?? $monthReport['daily_data'] ?? [];
            if (!is_array($dailyData) && method_exists($dailyData, 'toArray')) {
                $dailyData = $dailyData->toArray();
            }

            foreach ($dailyData as $day) {
                $sales = $day['sales'] ?? [];
                if (!is_array($sales) && method_exists($sales, 'toArray')) {
                    $sales = $sales->toArray();
                }

                foreach ($sales as $sale) {
                    $total = (float) ($sale['total'] ?? 0);
                    $paid = (float) ($sale['paid'] ?? 0);
                    $due = (float) ($sale['due'] ?? 0);

                    $totalAmount += $total;
                    $totalPaid += $paid;
                    $totalDue += $due;

                    $paymentDetails = [];
                    $payments = $sale['payments'] ?? [];
                    if (!is_array($payments) && method_exists($payments, 'toArray')) {
                        $payments = $payments->toArray();
                    }

                    if (!empty($payments)) {
                        foreach ($payments as $payment) {
                            $desc = ucfirst($payment['method'] ?? 'cash') . ': ' . number_format((float) ($payment['amount'] ?? 0), 2);
                            $bankName = $payment['bank_account']['name'] ?? $payment['bank_name'] ?? null;
                            $accNumber = $payment['bank_account']['account'] ?? $payment['account_number'] ?? null;
                            if ($bankName) {
                                $desc .= ' (' . $bankName . ($accNumber ? ' - ' . $accNumber : '') . ')';
                            }
                            $paymentDetails[] = $desc;
                        }
                    }

                    $rows[] = [
                        $sl++,
                        $day['date'] ?? '',
                        $sale['created_at'] ?? '',
                        $sale['invoice_no'] ?? '',
                        $sale['customer'] ?? 'Walk-in Customer',
                        $total,
                        $paid,
                        $due,
                        strtoupper($sale['payment_status'] ?? 'PAID'),
                        implode('; ', $paymentDetails),
                    ];
                }
            }
        }

        // Summary row
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            'Total Sales: ' . ($sl - 1),
            $totalAmount,
            $totalPaid,
            $totalDue,
            '',
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1; // including header

        // Header Style
        $sheet->getStyle('A1:J1')->applyFromArray([
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

        // Format numeric columns (Total, Paid, Due)
        $sheet->getStyle("F2:H{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Total Row Styling
        $sheet->getStyle("A{$totalRows}:J{$totalRows}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0F172A'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'], // Slate 200
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ]);

        return [];
    }
}
