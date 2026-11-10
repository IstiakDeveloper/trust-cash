<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

/** Append-only sales subledger shared by product and financial reporting. */
class SaleAccounting
{
    public function record(SaleItem $item, ?string $effectiveDate = null): void
    {
        $sale = Sale::withTrashed()->findOrFail($item->sale_id);
        $date = $effectiveDate ?? $sale->business_date ?? $sale->created_at->toDateString();
        $gross = (string) $item->subtotal;
        $discount = bccomp((string) $sale->subtotal, '0', 6) > 0
            ? bcmul(bcdiv((string) $sale->discount, (string) $sale->subtotal, 12), $gross, 6) : '0';
        $net = bcsub($gross, $discount, 6);
        $taxBase = bcsub((string) $sale->subtotal, (string) $sale->discount, 6);
        $tax = bccomp($taxBase, '0', 6) > 0 ? bcmul(bcdiv($net, $taxBase, 12), (string) ($sale->tax ?? 0), 6) : '0';
        $prior = DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('sale_id', $sale->id)
            ->whereIn('source_id', SaleItem::where('sale_id', $sale->id)->select('id'))->get();
        $calculator = app(InventoryReport::class);
        if (bccomp(bcadd($calculator->sum($prior, 'gross'), $gross, 6), (string) $sale->subtotal, 6) === 0) {
            $discount = bcsub((string) $sale->discount, $calculator->sum($prior, 'discount'), 6);
            $net = bcsub($gross, $discount, 6);
            $tax = bcsub((string) ($sale->tax ?? 0), $calculator->sum($prior, 'tax'), 6);
        }
        if ($item->discount_amount !== null) {
            $discount = (string) $item->discount_amount;
        }
        if ($item->tax_amount !== null) {
            $tax = (string) $item->tax_amount;
        }
        $net = bcsub($gross, $discount, 6);
        if (bccomp($net, '0', 6) < 0) {
            throw new \LogicException('Discount cannot exceed sale subtotal.');
        }
        DB::table('sale_accounting_entries')->insert([
            'source_type' => 'sale', 'source_id' => $item->id, 'sale_id' => $sale->id,
            'product_id' => $item->product_id, 'product_variant_id' => $item->product_variant_id,
            'customer_id' => $sale->customer_id, 'bank_account_id' => $sale->bank_account_id,
            'date' => $date, 'quantity' => $item->quantity,
            'gross' => $gross, 'discount' => $discount, 'net' => $net,
            'tax' => $tax,
            'cost' => Product::withTrashed()->find($item->product_id)?->isService() ? '0' : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(FinancialLedger::class)->pair('sale_items', $item->id, 'receivable:'.($sale->customer_id ?? 0), 'sales', $net, $date);
        if (Product::withTrashed()->find($item->product_id)?->isService()) {
            DB::table('sale_items')->where('id', $item->id)->update(['cost_total' => 0, 'costing_method' => DB::table('inventory_policies')->where('id', 1)->value('method')]);
        }
    }

    public function reverse(SaleItem $item): void
    {
        $entry = DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('source_id', $item->id)->first();
        if (! $entry) {
            throw new \LogicException('Initialize historical sales accounting before changing this sale.');
        }
        $stock = ProductStock::where('sale_item_id', $item->id)->where('type', 'sale')->first();
        if ($stock && ! ProductStock::where('source_stock_id', $stock->id)->where('type', 'sale_reversal')->exists()) {
            throw new \LogicException('Reverse the original stock allocation before cancelling this sale line.');
        }
        if (DB::table('sale_return_items')->where('sale_item_id', $item->id)->exists()) {
            throw new \LogicException('A sale with returns cannot be edited or cancelled.');
        }
        $values = (array) $entry;
        unset($values['id']);
        foreach (['quantity', 'gross', 'discount', 'net', 'tax', 'cost'] as $field) {
            if ($values[$field] === null) {
                throw new \LogicException('Sale stock costing is incomplete.');
            }
            $values[$field] = bcmul((string) $values[$field], '-1', 6);
        }
        $values['source_type'] = 'sale_reversal';
        $values['date'] = now()->toDateString();
        $values['created_at'] = $values['updated_at'] = now();
        DB::table('sale_accounting_entries')->insertOrIgnore($values);
        app(FinancialLedger::class)->reverse('sale_items', $item->id);
    }
}
