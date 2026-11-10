<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleReturnPosting
{
    public function post(array $data): SaleReturn
    {
        return DB::transaction(function () use ($data) {
            DB::table('inventory_policies')->lockForUpdate()->find(1);
            $key = $data['idempotency_key'] ?? null;
            $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
            if ($key && ($existing = SaleReturn::where('request_key', $key)->first())) {
                if ($existing->request_hash !== $hash) {
                    $this->fail('This request key belongs to a different return.');
                }

                return $existing;
            }
            $data['return_date'] = Carbon::parse($data['return_date'])->toDateString();
            $sale = Sale::whereKey($data['sale_id'])->lockForUpdate()->firstOrFail();
            if (($data['customer_id'] ?? null) != $sale->customer_id) {
                $this->fail('Return customer must match the original invoice.');
            }
            if ($data['return_date'] < ($sale->business_date ?? $sale->created_at->toDateString()) || $data['return_date'] > now()->toDateString()) {
                $this->fail('Return date must be between the sale date and today.');
            }
            $return = SaleReturn::create([
                'return_number' => 'RET-'.Str::ulid(), 'sale_id' => $sale->id, 'customer_id' => $sale->customer_id,
                'bank_account_id' => $data['bank_account_id'] ?? null, 'return_date' => $data['return_date'],
                'total_amount' => 0, 'refund_amount' => 0, 'refund_status' => $data['refund_status'],
                'reason' => $data['reason'] ?? null, 'created_by' => auth()->id(),
                'request_key' => $key, 'request_hash' => $hash,
            ]);
            $total = '0';
            foreach ($data['items'] as $input) {
                $line = SaleItem::where('sale_id', $sale->id)->whereKey($input['sale_item_id'])->lockForUpdate()->firstOrFail();
                if ($line->product_id != $input['product_id'] || $line->product_variant_id != ($input['product_variant_id'] ?? null)) {
                    $this->fail('Return item does not match the original invoice line.');
                }
                $original = DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('source_id', $line->id)->first();
                if (! $original || $original->cost === null) {
                    $this->fail('Original sale costing must be reconciled before returning it.');
                }
                $returned = (string) SaleReturnItem::where('sale_item_id', $line->id)->sum('quantity');
                $available = bcsub((string) $line->quantity, $returned, 6);
                $quantity = DecimalInput::normalize($input['quantity'], 6);
                if (bccomp($quantity, '0', 6) <= 0 || bccomp($quantity, $available, 6) > 0) {
                    $this->fail('Return quantity exceeds the quantity remaining on this invoice.');
                }
                // Round cumulative entitlements, then subtract previous credits. Repeated
                // partial returns therefore cannot refund more tax/revenue than the invoice.
                $ratio = bcdiv(bcadd($returned, $quantity, 6), (string) $line->quantity, 12);
                $prior = DB::table('sale_accounting_entries')->where('source_type', 'return')->where('sale_id', $sale->id)
                    ->whereIn('source_id', SaleReturnItem::where('sale_item_id', $line->id)->select('id'))->get();
                $calculator = app(InventoryReport::class);
                $last = bccomp($quantity, $available, 6) === 0;
                $grossTarget = $last ? (string) $original->gross : bcadd(bcmul((string) $original->gross, $ratio, 8), '0.005', 2);
                $netTarget = $last ? (string) $original->net : bcadd(bcmul((string) $original->net, $ratio, 8), '0.005', 2);
                $taxTarget = $last ? (string) $original->tax : bcadd(bcmul((string) $original->tax, $ratio, 8), '0.005', 2);
                $gross = bcadd($grossTarget, $calculator->sum($prior, 'gross'), 6);
                $net = bcadd($netTarget, $calculator->sum($prior, 'net'), 6);
                $tax = bcadd($taxTarget, $calculator->sum($prior, 'tax'), 6);
                $discount = bcsub($gross, $net, 6);
                $returnItem = SaleReturnItem::create([
                    'sale_return_id' => $return->id, 'sale_item_id' => $line->id,
                    'product_id' => $line->product_id, 'product_variant_id' => $line->product_variant_id,
                    'quantity' => $quantity, 'unit_price' => bcdiv(bcadd($net, $tax, 6), $quantity, 8), 'subtotal' => bcadd($net, $tax, 6),
                ]);
                $cost = '0';
                if (! Product::withTrashed()->findOrFail($line->product_id)->isService()) {
                    $source = ProductStock::where('sale_item_id', $line->id)->where('type', 'sale')->firstOrFail();
                    $stock = ProductStock::create([
                        'product_id' => $line->product_id, 'product_variant_id' => $line->product_variant_id,
                        'quantity' => $quantity, 'source_stock_id' => $source->id, 'sale_return_item_id' => $returnItem->id,
                        'type' => 'sale_return', 'date' => $data['return_date'], 'total_cost' => 0, 'unit_cost' => 0,
                        'selling_price' => $source->selling_price, 'note' => 'Return '.$return->return_number, 'created_by' => auth()->id(),
                    ]);
                    $cost = (string) $stock->total_cost;
                    StockMovement::create([
                        'product_id' => $line->product_id, 'reference_type' => SaleReturn::class, 'reference_id' => $return->id,
                        'quantity' => $quantity, 'before_quantity' => bcsub((string) $stock->available_quantity, $quantity, 6),
                        'after_quantity' => $stock->available_quantity, 'type' => 'in', 'created_by' => auth()->id(),
                    ]);
                } else {
                    $returnItem->update(['cost_total' => 0]);
                }
                DB::table('sale_accounting_entries')->insert([
                    'source_type' => 'return', 'source_id' => $returnItem->id, 'sale_id' => $sale->id,
                    'product_id' => $line->product_id, 'product_variant_id' => $line->product_variant_id,
                    'date' => $data['return_date'], 'quantity' => bcmul($quantity, '-1', 6),
                    'customer_id' => $sale->customer_id, 'bank_account_id' => $data['bank_account_id'] ?? null,
                    'gross' => bcmul($gross, '-1', 6), 'discount' => bcmul($discount, '-1', 6),
                    'net' => bcmul($net, '-1', 6), 'cost' => bcmul($cost, '-1', 6), 'created_at' => now(), 'updated_at' => now(),
                    'tax' => bcmul($tax, '-1', 6),
                ]);
                app(FinancialLedger::class)->pair('sale_return_items', $returnItem->id, 'sales', 'receivable:'.($sale->customer_id ?? 0), $net, $data['return_date']);
                app(FinancialLedger::class)->pair('sale_return_tax', $returnItem->id, 'tax_payable', 'receivable:'.($sale->customer_id ?? 0), $tax, $data['return_date']);
                $total = bcadd($total, bcadd($net, $tax, 6), 6);
            }
            $total = bcadd($total, '0.005', 2);
            if ($data['refund_status'] === 'completed') {
                $refunded = (string) SaleReturn::where('sale_id', $sale->id)->where('refund_status', 'completed')->where('id', '!=', $return->id)->sum('refund_amount');
                if (bccomp($total, bcsub((string) $sale->paid, $refunded, 2), 2) > 0) {
                    $this->fail('Cash refund exceeds the amount collected. Credit the unpaid balance instead.');
                }
                if (empty($data['bank_account_id'])) {
                    $this->fail('Choose the cash or bank account for the refund.');
                }
                BankTransaction::create([
                    'bank_account_id' => $data['bank_account_id'], 'transaction_type' => 'out', 'amount' => $total,
                    'category' => 'customer_refund', 'description' => 'Customer Sale Refund: #'.$return->return_number,
                    'date' => $data['return_date'], 'created_by' => auth()->id(),
                ]);
                app(FinancialLedger::class)->pair('sale_return_refund', $return->id, 'receivable:'.($sale->customer_id ?? 0), 'clearing', $total, $data['return_date'], 'customer_refund', $data['bank_account_id']);
            } else {
                if (! $sale->customer_id) {
                    $this->fail('A walk-in return must be refunded through a cash/bank account.');
                }
                Customer::whereKey($sale->customer_id)->decrement('balance', $total);
                $newDue = bcsub((string) $sale->due, $total, 2);
                $sale->update(['due' => bccomp($newDue, '0', 2) > 0 ? $newDue : '0', 'payment_status' => bccomp($newDue, '0', 2) > 0 ? 'partial' : 'paid']);
            }
            $return->update(['total_amount' => $total, 'refund_amount' => $total]);

            return $return;
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['items' => $message]);
    }
}
