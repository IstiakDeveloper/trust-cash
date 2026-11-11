<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\FixedAssetItem;
use App\Models\Fund;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Observers\AccountingObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Additive compatibility cutover. Never rewrites/deletes the original business records. */
class LegacyInventoryCarryForward
{
    public function ensure(): void
    {
        $ledger = app(FinancialLedger::class);
        if ($ledger->initializing || ! Schema::hasTable('inventory_policies')) {
            return;
        }
        $policy = DB::table('inventory_policies')->find(1);
        if (! $policy || $policy->accounting_ready) {
            return;
        }
        if (! Schema::hasTable('legacy_inventory_openings')) {
            throw ValidationException::withMessages(['accounting' => 'Run the latest tenant migrations to restore legacy stock and reports.']);
        }
        DB::transaction(function () use ($ledger) {
            $policy = DB::table('inventory_policies')->lockForUpdate()->find(1);
            if ($policy->accounting_ready) {
                return;
            }
            if (DB::table('product_stocks')->whereNull('deleted_at')->whereNotNull('costing_method')->exists()) {
                throw ValidationException::withMessages(['accounting' => 'Partially initialized inventory needs review. Existing records were preserved; automatic carry-forward was not applied.']);
            }
            $ledger->initializing = true;
            try {
                $this->inventory($policy->method);
                $this->sales();
                $this->documents();
                $this->preserveStoredBalances();
                DB::table('inventory_policies')->where('id', 1)->update([
                    'accounting_ready' => true, 'legacy_cutover_at' => now(), 'locked' => false,
                    'effective_from' => now()->toDateString(), 'updated_at' => now(),
                ]);
            } finally {
                $ledger->initializing = false;
            }
        }, 3);
    }

    private function inventory(string $method): void
    {
        $ledger = app(FinancialLedger::class);
        $calculator = app(InventoryReport::class);
        $products = Product::withTrashed()->get()->keyBy('id');
        $groups = DB::table('product_stocks')->whereNull('deleted_at')->orderBy('date')->orderBy('id')->get()
            ->groupBy(fn ($row) => $row->product_id.':'.($row->product_variant_id ?? 0));
        foreach ($groups as $key => $rows) {
            $product = $products->get($rows->first()->product_id);
            if (! $product || $product->isService()) {
                continue;
            }
            $purchases = $rows->where('type', 'purchase')->where('quantity', '>', 0);
            $purchased = $calculator->sum($purchases, 'quantity');
            $purchaseValue = $calculator->sum($purchases, 'total_cost');
            $average = bccomp($purchased, '0', 6) > 0
                ? bcdiv($purchaseValue, $purchased, 12)
                : (string) ($product->cost_price ?? 0);
            $quantity = $calculator->sum($rows, 'quantity');
            $value = bccomp($purchased, '0', 6) > 0
                ? bcdiv(bcmul($purchaseValue, $quantity, 12), $purchased, 6)
                : bcmul($quantity, $average, 6);
            $documentValue = '0';
            $lastDate = now()->toDateString();
            foreach ($rows as $row) {
                $incoming = bccomp((string) $row->quantity, '0', 6) > 0;
                $cost = $row->type === 'purchase' && $incoming ? (string) $row->total_cost
                    : bcmul(ltrim((string) $row->quantity, '-'), $average, 6);
                DB::table('legacy_inventory_values')->insert(['stock_id' => $row->id, 'reporting_cost' => $cost]);
                $documentValue = $incoming ? bcadd($documentValue, $cost, 6) : bcsub($documentValue, $cost, 6);
                $date = $row->date ?: substr($row->created_at, 0, 10);
                $lastDate = max($lastDate, $date);
                // Invoice snapshots own historical COGS. Unmatched legacy movements
                // remain visible in clearing instead of inventing an invoice link.
                $counter = 'clearing';
                $ledger->pair('legacy_product_stocks', $row->id,
                    $incoming ? 'inventory:'.$key : $counter, $incoming ? $counter : 'inventory:'.$key,
                    $cost, $date);
            }
            $adjustment = bcsub($value, $documentValue, 6);
            // Zero movement: the historical quantity already exists in product_stocks.
            // Only the physical layer is new; adding Q again would double stock.
            $openingId = DB::table('product_stocks')->insertGetId([
                'product_id' => $product->id, 'product_variant_id' => $rows->first()->product_variant_id,
                'quantity' => 0, 'total_quantity' => $quantity, 'available_quantity' => $quantity,
                'total_cost' => $adjustment, 'unit_cost' => $average, 'inventory_value' => $value,
                'type' => 'legacy_opening', 'costing_method' => $method,
                'lot_remaining_quantity' => bccomp($quantity, '0', 6) > 0 ? $quantity : '0',
                'lot_remaining_value' => bccomp($quantity, '0', 6) > 0 ? $value : '0',
                'lot_number' => 'OPENING-'.$product->id.'-'.($rows->first()->product_variant_id ?? 0),
                'selling_price' => $product->selling_price, 'date' => $lastDate,
                'note' => 'Existing stock carried forward; original movements retained.',
                'created_by' => $rows->last()->created_by, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('legacy_inventory_openings')->insert([
                'product_id' => $product->id, 'variant_key' => $rows->first()->product_variant_id ?? 0,
                'opening_stock_id' => $openingId, 'quantity' => $quantity, 'value' => $value,
                'unit_cost' => $average, 'source_stock_ids' => $rows->pluck('id')->toJson(), 'created_at' => now(),
            ]);
            DB::table('inventory_balances')->insert([
                'product_id' => $product->id, 'variant_key' => $rows->first()->product_variant_id ?? 0,
                'quantity' => $quantity, 'value' => $value, 'last_effective_date' => $lastDate,
            ]);
            $ledger->pair('legacy_inventory_openings', $openingId, 'inventory:'.$key, 'opening_equity', $adjustment, $lastDate);
        }
    }

    private function sales(): void
    {
        $ledger = app(FinancialLedger::class);
        $calculator = app(SaleAmounts::class);
        foreach (Sale::with('items')->orderBy('id')->get() as $sale) {
            $discounts = $calculator->allocate($sale->items->pluck('subtotal')->all(), $sale->discount);
            $nets = $sale->items->values()->map(fn ($item, $i) => bcsub((string) $item->subtotal, $discounts[$i], 2))->all();
            $taxes = $calculator->allocate($nets, $sale->tax ?? 0);
            foreach ($sale->items->values() as $i => $item) {
                if (DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('source_id', $item->id)->exists()) {
                    continue;
                }
                $opening = DB::table('legacy_inventory_openings')->where('product_id', $item->product_id)
                    ->where('variant_key', $item->product_variant_id ?? 0)->first();
                $product = Product::withTrashed()->find($item->product_id);
                $unitCost = $opening && bccomp((string) $opening->quantity, '0', 6) !== 0
                    ? bcdiv((string) $opening->value, (string) $opening->quantity, 12)
                    : (string) ($opening->unit_cost ?? $product?->cost_price ?? 0);
                $cost = $product?->isService() ? '0' : bcmul((string) $item->quantity, $unitCost, 6);
                DB::table('legacy_sale_openings')->insert([
                    'sale_item_id' => $item->id, 'quantity' => $item->quantity, 'cost' => $cost,
                ]);
                DB::table('sale_accounting_entries')->insert([
                    'source_type' => 'sale', 'source_id' => $item->id, 'sale_id' => $sale->id,
                    'product_id' => $item->product_id, 'product_variant_id' => $item->product_variant_id,
                    'customer_id' => $sale->customer_id, 'bank_account_id' => $sale->bank_account_id,
                    'date' => $sale->business_date ?? $sale->created_at->toDateString(),
                    'quantity' => $item->quantity, 'gross' => $item->subtotal, 'discount' => $discounts[$i],
                    'net' => $nets[$i], 'tax' => $taxes[$i], 'cost' => $cost, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $ledger->pair('sale_items', $item->id, 'receivable:'.($sale->customer_id ?? 0), 'sales', $nets[$i], $sale->business_date ?? $sale->created_at->toDateString());
                $ledger->pair('legacy_sale_costs', $item->id, 'cogs', 'clearing', $cost, $sale->business_date ?? $sale->created_at->toDateString());
            }
        }
        foreach (DB::table('sale_return_items as item')->join('sale_returns as header', 'header.id', '=', 'item.sale_return_id')
            ->get(['item.*', 'header.sale_id', 'header.customer_id', 'header.bank_account_id', 'header.return_date']) as $item) {
            $matches = SaleItem::where('sale_id', $item->sale_id)->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)->get();
            if ($item->sale_item_id || $matches->count() === 1) {
                DB::table('legacy_sale_return_links')->insert([
                    'return_item_id' => $item->id, 'sale_item_id' => $item->sale_item_id ?? $matches->first()->id,
                ]);
            } else {
                DB::table('legacy_sale_openings')->whereIn('sale_item_id', $matches->pluck('id'))
                    ->update(['return_history_ambiguous' => true]);
            }
            $opening = DB::table('legacy_inventory_openings')->where('product_id', $item->product_id)->where('variant_key', $item->product_variant_id ?? 0)->first();
            DB::table('sale_accounting_entries')->insertOrIgnore([
                'source_type' => 'return', 'source_id' => $item->id, 'sale_id' => $item->sale_id,
                'product_id' => $item->product_id, 'product_variant_id' => $item->product_variant_id,
                'customer_id' => $item->customer_id, 'bank_account_id' => $item->bank_account_id, 'date' => $item->return_date,
                'quantity' => bcmul((string) $item->quantity, '-1', 6), 'gross' => bcmul((string) $item->subtotal, '-1', 6),
                'discount' => 0, 'net' => bcmul((string) $item->subtotal, '-1', 6), 'tax' => 0,
                'cost' => bcmul(bcmul((string) $item->quantity, (string) ($opening->unit_cost ?? 0), 6), '-1', 6),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $ledger->pair('sale_return_items', $item->id, 'sales', 'receivable:'.($item->customer_id ?? 0), $item->subtotal, $item->return_date);
            $returnCost = bcmul((string) $item->quantity, (string) ($opening->unit_cost ?? 0), 6);
            $ledger->pair('legacy_return_costs', $item->id, 'clearing', 'cogs', $returnCost, $item->return_date);
        }
        foreach (DB::table('sale_returns')->get() as $return) {
            if ($return->refund_status !== 'credited_to_due') {
                $ledger->pair('sale_return_refund', $return->id, 'receivable:'.($return->customer_id ?? 0), 'clearing',
                    $return->refund_amount ?? 0, $return->return_date, 'sale_refund', $return->bank_account_id);
            }
        }
    }

    private function documents(): void
    {
        $observer = app(AccountingObserver::class);
        foreach ([BankAccount::class, BankTransaction::class, Supplier::class, Purchase::class, SupplierPayment::class,
            Sale::class, SalePayment::class, Expense::class, ExtraIncome::class, Fund::class, FixedAssetItem::class] as $modelClass) {
            $query = in_array($modelClass, [BankAccount::class, Supplier::class], true) ? $modelClass::withTrashed() : $modelClass::query();
            foreach ($query->orderBy('id')->get() as $document) {
                if (DB::table('ledger_documents')->where('source_type', $document->getTable())->where('source_id', $document->id)->exists()) {
                    continue;
                }
                if ($document instanceof Sale) {
                    $document->paid = bcsub((string) $document->paid, (string) $document->payments()->sum('amount'), 2);
                }
                $observer->post($document);
            }
        }
    }

    private function preserveStoredBalances(): void
    {
        $ledger = app(FinancialLedger::class);
        $balances = [];
        foreach (DB::table('ledger_lines')->get() as $line) {
            $balances[$line->account] = bcadd($balances[$line->account] ?? '0', (string) $line->amount, 6);
        }
        foreach ([Customer::class => ['receivable:', 'balance', 1], Supplier::class => ['payable:', 'current_balance', -1], BankAccount::class => ['cash:', 'current_balance', 1]] as $model => [$prefix, $field, $sign]) {
            foreach ($model::withTrashed()->get() as $source) {
                $account = $prefix.$source->id;
                $stored = bcmul((string) $source->$field, (string) $sign, 6);
                $computed = $balances[$account] ?? '0';
                $difference = bcsub($stored, $computed, 6);
                if (bccomp($difference, '0', 6) === 0) {
                    continue;
                }
                // Explicit opening evidence: preserve the balance stored before this upgrade.
                // Never disguise missing historical evidence as sales, expenses or profit.
                $id = DB::table('legacy_balance_carry_forwards')->insertGetId([
                    'account' => $account, 'stored_balance' => $stored, 'document_balance' => $computed,
                    'difference' => $difference, 'date' => now()->toDateString(), 'created_at' => now(),
                ]);
                $ledger->pair($prefix === 'cash:' ? 'legacy_bank_opening' : 'legacy_balance_carry_forward', $id,
                    $account, 'opening_equity', $difference, now()->toDateString(), 'legacy_balance_carry_forward');
            }
        }
    }
}
