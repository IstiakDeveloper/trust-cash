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

class StockReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $reports;
    protected array $summary;
    protected string $title;

    /**
     * @param array|\Illuminate\Support\Collection $reports
     * @param array $summary
     * @param string $title
     */
    public function __construct($reports, array $summary = [], string $title = 'Stock Report')
    {
        if (is_array($reports)) {
            $this->reports = $reports;
        } elseif (method_exists($reports, 'toArray')) {
            $this->reports = $reports->toArray();
        } else {
            $this->reports = (array) $reports;
        }
        $this->summary = $summary;
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
            'Product Name',
            'SKU',
            'Category',
            'Unit',
            'Opening Stock',
            'Stock In (+)',
            'Stock Out (-)',
            'Available Stock',
            'Unit Cost (BDT)',
            'Total Stock Value (BDT)',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $sl = 1;

        $totalOpening = 0;
        $totalIn = 0;
        $totalOut = 0;
        $totalAvailable = 0;
        $totalValue = 0;

        foreach ($this->reports as $item) {
            $productName = $item['product']['name'] ?? $item['product_name'] ?? '';
            $sku = $item['product']['sku'] ?? $item['sku'] ?? '—';
            $category = $item['product']['category'] ?? $item['category_name'] ?? '—';
            $unit = $item['product']['unit'] ?? $item['unit'] ?? 'Pcs';

            $opening = (float) ($item['summary']['opening_stock'] ?? $item['initial_stock'] ?? $item['opening_stock'] ?? 0);
            $in = (float) ($item['summary']['total_purchased'] ?? $item['total_stock_in'] ?? $item['total_in'] ?? 0);
            $out = (float) ($item['summary']['total_sold'] ?? $item['total_stock_out'] ?? $item['total_out'] ?? 0);
            $available = (float) ($item['summary']['current_stock'] ?? $item['available_stock'] ?? $item['current_stock'] ?? 0);
            $unitCost = (float) ($item['summary']['avg_cost'] ?? $item['current_unit_cost'] ?? $item['cost_price'] ?? 0);
            $val = (float) ($item['summary']['stock_value'] ?? $item['current_stock_value'] ?? $item['total_stock_value'] ?? ($available * $unitCost));

            $totalOpening += $opening;
            $totalIn += $in;
            $totalOut += $out;
            $totalAvailable += $available;
            $totalValue += $val;

            $rows[] = [
                $sl++,
                $productName,
                $sku,
                $category,
                $unit,
                $opening,
                $in,
                $out,
                $available,
                $unitCost,
                $val,
            ];
        }

        // Summary row
        $rows[] = [
            'TOTAL',
            'Total Products: ' . ($sl - 1),
            '',
            '',
            '',
            $totalOpening,
            $totalIn,
            $totalOut,
            $totalAvailable,
            '',
            $totalValue,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header style
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'], // Teal 700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Numeric column formats
        $sheet->getStyle("F2:I{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("J2:K{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Total row style
        $sheet->getStyle("A{$totalRows}:K{$totalRows}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '134E4A'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCFBF1'], // Teal 100
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ]);

        return [];
    }
}
