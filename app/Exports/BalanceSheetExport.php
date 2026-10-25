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

class BalanceSheetExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $fundAndLiabilities;
    protected array $propertyAndAssets;
    protected string $startDate;
    protected string $endDate;

    public function __construct(array $fundAndLiabilities, array $propertyAndAssets, string $startDate = '', string $endDate = '')
    {
        $this->fundAndLiabilities = $fundAndLiabilities;
        $this->propertyAndAssets = $propertyAndAssets;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Balance Sheet';
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

        $rows[] = ['INFO', 'Statement of Financial Position (Balance Sheet)', 'From: ' . $this->startDate, 'To: ' . $this->endDate];
        $rows[] = ['', '', '', ''];

        // --- PROPERTY & ASSETS ---
        $rows[] = ['ASSET', '[1. PROPERTY & ASSETS (সম্পদসমূহ)]', '', ''];

        $fixedTotal = $this->amount($this->propertyAndAssets['fixed_assets'] ?? 0);
        foreach ($this->propertyAndAssets['fixed_assets_breakdown'] ?? [] as $fa) {
            $amount = (float) ($fa['amount'] ?? $fa['period'] ?? 0);
            $rows[] = [
                'ASSET',
                '  • Fixed Asset: ' . ($fa['name'] ?? 'Asset'),
                $amount,
                $amount,
            ];
        }
        $rows[] = ['ASSET', 'Subtotal Fixed Assets (স্থায়ী সম্পদ)', $fixedTotal, $fixedTotal];

        $stock = $this->amount($this->propertyAndAssets['stock_value'] ?? $this->propertyAndAssets['closing_stock'] ?? 0);
        $rows[] = ['ASSET', 'Stock in Hand (মজুদ পণ্যের মূল্য)', $stock, $stock];

        $receivable = $this->amount($this->propertyAndAssets['customer_due'] ?? 0);
        $rows[] = ['ASSET', 'Accounts Receivable (গ্রাহকদের নিকট পাওনা)', $receivable, $receivable];

        $cash = $this->amount($this->propertyAndAssets['bank_balance'] ?? $this->propertyAndAssets['closing_cash'] ?? 0);
        $rows[] = ['ASSET', 'Cash at Bank & In Hand (নগদ ও ব্যাংক স্থিতি)', $cash, $cash];

        $assetTotal = $this->amount($this->propertyAndAssets['total'] ?? 0);
        $rows[] = ['ASSET TOTAL', 'TOTAL PROPERTY & ASSETS (সর্বমোট সম্পদ)', $assetTotal, $assetTotal];

        $rows[] = ['', '', '', ''];

        // --- FUND & LIABILITIES ---
        $rows[] = ['LIABILITY', '[2. FUND & LIABILITIES (দায় ও মূলধন)]', '', ''];

        // Fund / Capital (Fund In minus Fund Out)
        $fundAmount = (float) ($this->fundAndLiabilities['fund']['period'] ?? $this->fundAndLiabilities['fund']['total']['period'] ?? 0);
        $rows[] = [
            'LIABILITY',
            'Fund (Capital) — Fund In minus Fund Out',
            $fundAmount,
            $fundAmount,
        ];

        // Opening capital is the bank balance typed when the account was created.
        $openingCapital = (float) ($this->fundAndLiabilities['opening_capital']['period'] ?? 0);
        $rows[] = [
            'LIABILITY',
            'Opening Capital (প্রারম্ভিক মূলধন)',
            $openingCapital,
            $openingCapital,
        ];

        $payable = $this->amount($this->fundAndLiabilities['supplier_due'] ?? 0);
        $rows[] = ['LIABILITY', 'Accounts Payable (সরবরাহকারী দেনা)', $payable, $payable];

        $netProfit = $this->amount($this->fundAndLiabilities['net_profit'] ?? 0);
        $rows[] = ['LIABILITY', 'Net Profit / Surplus (নীট লাভ / উদ্বৃত্ত)', $netProfit, $netProfit];

        $liabilityTotal = $this->amount($this->fundAndLiabilities['total'] ?? 0);
        $rows[] = ['LIABILITY TOTAL', 'TOTAL FUND & LIABILITIES (সর্বমোট দায় ও তহবিল)', $liabilityTotal, $liabilityTotal];

        return $rows;
    }

    private function amount(mixed $value): float
    {
        if (! is_array($value)) {
            return (float) $value;
        }

        if (array_key_exists('period', $value) || array_key_exists('cumulative', $value)) {
            return (float) ($value['period'] ?? $value['cumulative'] ?? 0);
        }

        $total = $value['total'] ?? 0;
        if (is_array($total)) {
            return (float) ($total['period'] ?? $total['cumulative'] ?? 0);
        }

        return (float) $total;
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
                'startColor' => ['rgb' => '1E293B'], // Slate 900
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
