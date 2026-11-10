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

class BankTransactionReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $transactions;

    protected float $previousBalance;

    protected string $title;

    public function __construct(array $transactions, float $previousBalance = 0, string $title = 'Bank Transactions')
    {
        $this->transactions = $transactions;
        $this->previousBalance = $previousBalance;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Fund In (BDT)',
            'Payment Received (BDT)',
            'Extra Income (BDT)',
            'Refund / Other Receipts (BDT)',
            'Total In (BDT)',
            'Purchase / Supplier Payment (BDT)',
            'Expenses (BDT)',
            'Asset Purchase (BDT)',
            'Fund Refund (BDT)',
            'Refund / Other Payments (BDT)',
            'Total Out (BDT)',
            'Day Net (BDT)',
            'Closing Balance (BDT)',
        ];
    }

    public function array(): array
    {
        if (isset($this->transactions[0]['in'])) {
            $rows = [['Opening balance', '', '', '', '', '', '', '', '', '', '', '', '', $this->previousBalance]];
            foreach ($this->transactions as $row) {
                $rows[] = [$row['date'], (float) $row['in']['fund'], (float) $row['in']['payment'],
                    (float) $row['in']['extra'], (float) bcadd($row['in']['refund'], $row['in']['other'], 6), (float) $row['in']['total'],
                    (float) bcadd($row['out']['supplier_payment'], $row['out']['purchase'], 6), (float) $row['out']['expense'],
                    (float) $row['out']['fixed_asset'], (float) $row['out']['fund'], (float) $row['out']['other'],
                    (float) $row['out']['total'], (float) bcsub($row['in']['total'], $row['out']['total'], 6), (float) $row['balance']];
            }

            return $rows;
        }
        $rows = [];

        // Opening balance row
        $rows[] = [
            'Opening Balance',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->previousBalance,
        ];

        $runningBalance = $this->previousBalance;
        $sumFundIn = 0;
        $sumPaymentReceive = 0;
        $sumExtraIncome = 0;
        $sumRefundIn = 0;
        $sumTotalIn = 0;

        $sumSupplierPayment = 0;
        $sumExpense = 0;
        $sumAssetPurchase = 0;
        $sumFundRefund = 0;
        $sumRefundOut = 0;
        $sumTotalOut = 0;

        foreach ($this->transactions as $tx) {
            $fundIn = (float) ($tx['fund_in'] ?? 0);
            $paymentReceive = (float) ($tx['payment_receive'] ?? 0);
            $extraIncome = (float) ($tx['extra_income'] ?? 0);
            $refundIn = (float) ($tx['refund'] ?? 0);
            $totalIn = (float) ($tx['total_in'] ?? ($fundIn + $paymentReceive + $extraIncome + $refundIn));

            $supplierPay = (float) ($tx['supplier_payment'] ?? 0);
            $expense = (float) ($tx['expense'] ?? 0);
            $assetPurchase = (float) ($tx['fixed_asset_purchase'] ?? 0);
            $fundRefund = (float) ($tx['fund_refund'] ?? 0);
            $refundOut = (float) ($tx['refund_out'] ?? 0);
            $totalOut = (float) ($tx['total_out'] ?? ($supplierPay + $expense + $assetPurchase + $fundRefund + $refundOut));

            $net = $totalIn - $totalOut;
            $runningBalance += $net;

            $sumFundIn += $fundIn;
            $sumPaymentReceive += $paymentReceive;
            $sumExtraIncome += $extraIncome;
            $sumRefundIn += $refundIn;
            $sumTotalIn += $totalIn;

            $sumSupplierPayment += $supplierPay;
            $sumExpense += $expense;
            $sumAssetPurchase += $assetPurchase;
            $sumFundRefund += $fundRefund;
            $sumRefundOut += $refundOut;
            $sumTotalOut += $totalOut;

            $rows[] = [
                $tx['date'] ?? '',
                $fundIn,
                $paymentReceive,
                $extraIncome,
                $refundIn,
                $totalIn,
                $supplierPay,
                $expense,
                $assetPurchase,
                $fundRefund,
                $refundOut,
                $totalOut,
                $net,
                $runningBalance,
            ];
        }

        // Summary row
        $rows[] = [
            'TOTAL',
            $sumFundIn,
            $sumPaymentReceive,
            $sumExtraIncome,
            $sumRefundIn,
            $sumTotalIn,
            $sumSupplierPayment,
            $sumExpense,
            $sumAssetPurchase,
            $sumFundRefund,
            $sumRefundOut,
            $sumTotalOut,
            $sumTotalIn - $sumTotalOut,
            $runningBalance,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header style
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '334155'], // Slate 700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Numeric columns B to N
        $sheet->getStyle("B2:N{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Total row style
        $sheet->getStyle("A{$totalRows}:N{$totalRows}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0F172A'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CBD5E1'], // Slate 300
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ]);

        return [];
    }
}
