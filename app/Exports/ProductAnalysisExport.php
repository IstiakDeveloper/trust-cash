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

class ProductAnalysisExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $products;
    protected array $totals;
    protected string $title;

    public function __construct(array $products, array $totals = [], string $title = 'Product Analysis')
    {
        $this->products = $products;
        $this->totals = $totals;
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
            'Opening Qty',
            'Opening Price',
            'Opening Value',
            'Buy Qty',
            'Buy Price',
            'Buy Total',
            'Sale Qty',
            'Sale Price',
            'Sale Subtotal',
            'Sale Discount',
            'Sale Total',
            'Profit / Unit',
            'Total Profit',
            'Profit %',
            'Available Stock',
            'Stock Value',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $sl = 1;

        foreach ($this->products as $p) {
            $rows[] = [
                $sl++,
                $p['product_name'] ?? ($p['name'] ?? ''),
                $p['product_model'] ?? ($p['sku'] ?? '—'),
                $p['category'] ?? ($p['category_name'] ?? '—'),
                $p['unit'] ?? 'Pcs',
                (float) ($p['before_quantity'] ?? 0),
                (float) ($p['before_price'] ?? 0),
                (float) ($p['before_value'] ?? 0),
                (float) ($p['buy_quantity'] ?? 0),
                (float) ($p['buy_price'] ?? 0),
                (float) ($p['total_buy_price'] ?? 0),
                (float) ($p['sale_quantity'] ?? 0),
                (float) ($p['sale_price'] ?? 0),
                (float) ($p['total_sale_price'] ?? 0),
                (float) ($p['sale_discount'] ?? 0),
                (float) ($p['sale_after_discount'] ?? 0),
                (float) ($p['profit_per_unit'] ?? 0),
                (float) ($p['total_profit'] ?? 0),
                round((float) ($p['profit_percentage'] ?? 0), 2) . '%',
                (float) ($p['available_quantity'] ?? 0),
                (float) ($p['available_stock_value'] ?? 0),
            ];
        }

        // Totals row
        $rows[] = [
            'TOTAL',
            'Total Products: ' . ($sl - 1),
            '',
            '',
            '',
            (float) ($this->totals['before_quantity'] ?? 0),
            '',
            (float) ($this->totals['before_value'] ?? 0),
            (float) ($this->totals['buy_quantity'] ?? 0),
            '',
            (float) ($this->totals['total_buy_price'] ?? 0),
            (float) ($this->totals['sale_quantity'] ?? 0),
            '',
            (float) ($this->totals['total_sale_price'] ?? 0),
            (float) ($this->totals['sale_discount'] ?? 0),
            (float) ($this->totals['sale_after_discount'] ?? 0),
            '',
            (float) ($this->totals['total_profit'] ?? 0),
            round((float) ($this->totals['profit_percentage'] ?? 0), 2) . '%',
            (float) ($this->totals['available_quantity'] ?? 0),
            (float) ($this->totals['available_stock_value'] ?? 0),
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = count($this->array()) + 1;

        // Header style
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
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

        // Numeric formats for columns F to U (except S which is string percentage)
        $sheet->getStyle("F2:R{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("T2:U{$totalRows}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Total row style
        $sheet->getStyle("A{$totalRows}:U{$totalRows}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '064E3B'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1FAE5'], // Emerald 100
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ]);

        return [];
    }
}
