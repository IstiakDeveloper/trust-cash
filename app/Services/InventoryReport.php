<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReport
{
    public function analysis($from, $through): array
    {
        $start = Carbon::parse($from)->toDateString();
        $end = Carbon::parse($through)->toDateString();
        if ($start > $end) {
            throw new \InvalidArgumentException('End date must follow start date.');
        }
        $stocks = DB::table('product_stocks')->whereNull('deleted_at')->where('date', '<=', $end)->orderBy('date')->orderBy('id')->get()->groupBy('product_id');
        $entries = DB::table('sale_accounting_entries')->whereBetween('date', [$start, $end])->get()->groupBy('product_id');
        $consumptions = DB::table('inventory_allocations as allocation')->join('product_stocks as stock', 'stock.id', '=', 'allocation.stock_id')
            ->where('stock.date', '<=', $end)->whereNull('stock.deleted_at')->get(['allocation.lot_id', 'allocation.quantity'])->groupBy('lot_id');
        $products = Product::withTrashed()->with(['category', 'unit'])->orderBy('name')->get()->map(function ($product) use ($stocks, $entries, $start, $consumptions) {
            $history = $stocks->get($product->id, collect());
            $sales = $entries->get($product->id, collect());
            if ($history->contains(fn ($row) => $row->costing_method === null) || $sales->contains(fn ($row) => $row->cost === null)) {
                throw ValidationException::withMessages(['accounting' => 'Historical stock and sale costing must be reconciled before reporting.']);
            }
            $opening = $history->filter(fn ($row) => $row->date < $start);
            $period = $history->filter(fn ($row) => $row->date >= $start);
            $purchases = $period->whereIn('type', ['purchase', 'purchase_reversal']);
            $beforeQuantity = $this->sum($opening, 'quantity');
            $beforeValue = $this->stockValue($opening);
            $closingQuantity = $this->sum($history, 'quantity');
            $closingValue = $this->stockValue($history);
            $buyQuantity = $this->sum($purchases, 'quantity');
            $buyValue = $this->stockValue($purchases);
            $saleQuantity = $this->sum($sales, 'quantity');
            $gross = $this->sum($sales, 'gross');
            $discount = $this->sum($sales, 'discount');
            $net = $this->sum($sales, 'net');
            $cost = $this->sum($sales, 'cost');
            $profit = bcsub($net, $cost, 6);

            return [
                'product_id' => $product->id, 'product_name' => $product->name, 'product_model' => $product->sku,
                'category' => $product->category?->name ?? 'Uncategorized', 'unit' => $product->unit?->name ?? '',
                'before_quantity' => $beforeQuantity, 'before_price' => $this->divide($beforeValue, $beforeQuantity), 'before_value' => $beforeValue,
                'buy_quantity' => $buyQuantity, 'buy_price' => $this->divide($buyValue, $buyQuantity), 'total_buy_price' => $buyValue,
                'sale_quantity' => $saleQuantity, 'sale_price' => $this->divide($gross, $saleQuantity),
                'total_sale_price' => $gross, 'sale_discount' => $discount, 'sale_after_discount' => $net,
                'sales_cost' => $cost, 'profit_per_unit' => $this->divide($profit, $saleQuantity), 'total_profit' => $profit,
                'profit_percentage' => bcmul($this->divide($profit, $net), '100', 6),
                'available_quantity' => $closingQuantity, 'available_stock_value' => $closingValue,
                'return_quantity' => bcmul($this->sum($sales->where('source_type', 'return'), 'quantity'), '-1', 6),
                'adjustment_quantity' => $this->sum($period->whereNotIn('type', ['purchase', 'sale', 'sale_return', 'sale_reversal']), 'quantity'),
                'lots' => $history->where('quantity', '>', 0)->map(fn ($lot) => [
                    'id' => $lot->id, 'number' => $lot->lot_number, 'received_quantity' => $lot->quantity,
                    'purchase_cost' => $lot->unit_cost, 'selling_price' => $lot->selling_price,
                    'variant_id' => $lot->product_variant_id,
                    'date' => $lot->date,
                    'remaining_quantity' => bcsub((string) $lot->quantity, $this->sum($consumptions->get($lot->id, collect()), 'quantity'), 6),
                ])->values(),
            ];
        })->filter(fn ($row) => $stocks->has($row['product_id']) || $entries->has($row['product_id']))->values();
        $totals = [];
        foreach (['before_quantity', 'before_value', 'buy_quantity', 'total_buy_price', 'sale_quantity', 'total_sale_price', 'sale_discount', 'sale_after_discount', 'sales_cost', 'total_profit', 'available_quantity', 'available_stock_value', 'return_quantity', 'adjustment_quantity'] as $field) {
            $totals[$field] = $this->sum($products, $field);
        }

        // Decimal arithmetic above is authoritative; convert only at the presentation boundary.
        return ['products' => $products->map(fn ($row, $i) => ['serial' => $i + 1] + $row), 'totals' => $totals];
    }

    public function stockValue($rows): string
    {
        $value = '0.000000';
        foreach ($rows as $row) {
            $value = bccomp((string) $row->quantity, '0', 6) > 0
                ? bcadd($value, (string) $row->total_cost, 6) : bcsub($value, (string) $row->total_cost, 6);
        }

        return $value;
    }

    public function sum($rows, string $field): string
    {
        $total = '0.000000';
        foreach ($rows as $row) {
            $total = bcadd($total, (string) data_get($row, $field, '0'), 6);
        }

        return $total;
    }

    private function divide(string $value, string $quantity): string
    {
        return bccomp($quantity, '0', 6) === 0 ? '0.000000' : bcdiv($value, $quantity, 6);
    }
}
