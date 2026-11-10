<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** All arithmetic is decimal; callers post inside the ProductStock save transaction. */
class InventoryValuation
{
    public function prepare(ProductStock $stock, bool $initializing = false): array
    {
        $policy = DB::table('inventory_policies')->lockForUpdate()->find(1);
        if (! $policy) {
            throw new \LogicException('Run inventory migrations before posting stock.');
        }
        $product = Product::withTrashed()->whereKey($stock->product_id)->lockForUpdate()->firstOrFail();
        if ($product->isService()) {
            $this->fail('Service products do not receive physical stock.');
        }
        $variant = (int) ($stock->product_variant_id ?? 0);
        if ($variant && ! DB::table('product_variants')->where('id', $variant)->where('product_id', $stock->product_id)->exists()) {
            $this->fail('The selected variant does not belong to this product.');
        }
        $scope = fn () => ProductStock::where('product_id', $stock->product_id)
            ->where('product_variant_id', $stock->product_variant_id);
        if ($stock->type === 'sale') {
            $line = SaleItem::find($stock->sale_item_id);
            if (! $line || $line->product_id != $stock->product_id || $line->product_variant_id != $stock->product_variant_id
                || bccomp((string) $line->quantity, ltrim((string) $stock->quantity, '-'), 6) !== 0
                || ProductStock::where('sale_item_id', $line->id)->where('type', 'sale')->when($stock->exists, fn ($q) => $q->where('id', '!=', $stock->id))->exists()) {
                $this->fail('Sale stock must reference its matching, unposted invoice line.');
            }
        }
        if (! $initializing && $scope()->whereNull('costing_method')->exists()) {
            $this->fail('Legacy stock needs reconciliation. Run inventory:initialize --dry-run, then initialize this tenant before posting.');
        }
        DB::table('inventory_balances')->insertOrIgnore([
            'product_id' => $stock->product_id, 'variant_key' => $variant, 'quantity' => 0, 'value' => 0,
        ]);
        $balance = DB::table('inventory_balances')->where('product_id', $stock->product_id)
            ->where('variant_key', $variant)->lockForUpdate()->first();
        $date = Carbon::parse($stock->date ?? $stock->created_at ?? now())->toDateString();
        if ($balance->last_effective_date && $date < $balance->last_effective_date) {
            $this->fail('This date precedes posted stock activity. Use a current dated correction to preserve historical costing.');
        }
        if (($policy->effective_from && $date < $policy->effective_from) || ($policy->locked_through && $date <= $policy->locked_through)) {
            $this->fail('This stock date is outside the open costing period.');
        }
        if ($date > now()->toDateString()) {
            $this->fail('Future stock postings are not allowed.');
        }
        $quantity = bcadd((string) $stock->quantity, '0', 6);
        if (bccomp($quantity, '0', 6) === 0) {
            $this->fail('Stock quantity must be non-zero.');
        }
        $allocations = [];
        $incoming = bccomp($quantity, '0', 6) > 0;
        if (($stock->type === 'sale' && $incoming)
            || ($stock->type === 'purchase_reversal' && $incoming)
            || (in_array($stock->type, ['purchase', 'sale_return', 'sale_reversal'], true) && ! $incoming)) {
            $this->fail('Stock direction does not match its document type.');
        }
        $cost = bcadd((string) ($stock->total_cost ?? '0'), '0', 6);
        if ($incoming && in_array($stock->type, ['sale_return', 'sale_reversal'], true)) {
            $cost = '0.000000';
            $original = ProductStock::whereKey($stock->source_stock_id)->lockForUpdate()->first();
            if (! $original || $original->type !== 'sale' || $original->product_id != $stock->product_id || $original->product_variant_id != $stock->product_variant_id) {
                $this->fail('A stock return must reference the original sale stock entry.');
            }
            $remaining = $quantity;
            foreach (DB::table('inventory_allocations')->where('stock_id', $original->id)->orderBy('id')->lockForUpdate()->get() as $allocation) {
                $available = bcsub((string) $allocation->quantity, (string) $allocation->returned_quantity, 6);
                $take = bccomp($available, $remaining, 6) < 0 ? $available : $remaining;
                if (bccomp($take, '0', 6) <= 0) {
                    continue;
                }
                $unreturnedCost = bcsub((string) $allocation->cost, (string) $allocation->returned_cost, 6);
                $part = bccomp($take, $available, 6) === 0 ? $unreturnedCost : bcmul(bcdiv($unreturnedCost, $available, 12), $take, 6);
                DB::table('inventory_allocations')->where('id', $allocation->id)->update([
                    'returned_quantity' => bcadd((string) $allocation->returned_quantity, $take, 6),
                    'returned_cost' => bcadd((string) $allocation->returned_cost, $part, 6),
                ]);
                $cost = bcadd($cost, $part, 6);
                $remaining = bcsub($remaining, $take, 6);
            }
            if (bccomp($remaining, '0', 6) > 0) {
                $this->fail('Return exceeds the unreturned quantity on the original sale.');
            }
        } elseif (! $incoming && $stock->type === 'purchase_reversal') {
            $source = $scope()->whereKey($stock->source_stock_id)->lockForUpdate()->first();
            $latestId = $scope()->max('id');
            if (! $source || $source->type !== 'purchase' || $source->id != $latestId
                || bccomp((string) $source->lot_remaining_quantity, (string) $source->quantity, 6) !== 0
                || bccomp(ltrim($quantity, '-'), (string) $source->quantity, 6) !== 0) {
                $this->fail('Only the latest untouched receipt can be cancelled. Later stock activity requires a reconciled supplier return.');
            }
            $cost = (string) $source->total_cost;
            DB::table('product_stocks')->where('id', $source->id)->update(['lot_remaining_quantity' => 0, 'lot_remaining_value' => 0]);
            $allocations[] = ['lot_id' => $source->id, 'quantity' => ltrim($quantity, '-'), 'cost' => $cost];
        } elseif (! $incoming) {
            $requested = bcmul($quantity, '-1', 6);
            if (bccomp((string) $balance->quantity, $requested, 6) < 0) {
                $this->fail('Insufficient stock.');
            }
            $remaining = $requested;
            $cost = '0.000000';
            $lots = $scope()->where('lot_remaining_quantity', '>', 0)->orderBy('date')->orderBy('id')->lockForUpdate()->get();
            $requestedLot = $stock->sale_item_id ? DB::table('sale_items')->where('id', $stock->sale_item_id)->value('lot_id') : null;
            // Physical selection may choose a lot; FIFO accounting remains FIFO.
            $physicalLots = $requestedLot ? $lots->sortBy(fn ($lot) => $lot->id == $requestedLot ? 0 : 1) : $lots;
            if ($requestedLot && ! $lots->contains('id', $requestedLot)) {
                $this->fail('The selected lot is unavailable for this product/variant.');
            }
            if ($requestedLot && bccomp((string) $lots->firstWhere('id', $requestedLot)->lot_remaining_quantity, $requested, 6) < 0) {
                $this->fail('The selected lot does not have enough stock. Split the sale into separate lot lines.');
            }
            // Cost layers and physical lots are separate under FIFO when a different lot is picked.
            // To keep traceability unambiguous, FIFO picking follows its valuation order.
            if ($requestedLot && $policy->method === 'fifo' && $lots->first()->id != $requestedLot) {
                $this->fail('FIFO uses the oldest available lot. Select that lot or leave automatic allocation enabled.');
            }
            $averageCost = bccomp((string) $balance->quantity, $requested, 6) === 0
                ? (string) $balance->value
                : bcmul(bcdiv((string) $balance->value, (string) $balance->quantity, 12), $requested, 6);
            foreach ($physicalLots as $lot) {
                $take = bccomp((string) $lot->lot_remaining_quantity, $remaining, 6) < 0 ? (string) $lot->lot_remaining_quantity : $remaining;
                if (bccomp($take, '0', 6) <= 0) {
                    continue;
                }
                $layerCost = bccomp($take, (string) $lot->lot_remaining_quantity, 6) === 0
                    ? (string) $lot->lot_remaining_value
                    : bcmul(bcdiv((string) $lot->lot_remaining_value, (string) $lot->lot_remaining_quantity, 12), $take, 6);
                $part = $policy->method === 'fifo' ? $layerCost
                    : (bccomp($take, $remaining, 6) === 0 ? bcsub($averageCost, $cost, 6)
                        : bcmul(bcdiv($averageCost, $requested, 12), $take, 6));
                DB::table('product_stocks')->where('id', $lot->id)->update([
                    'lot_remaining_quantity' => bcsub((string) $lot->lot_remaining_quantity, $take, 6),
                    'lot_remaining_value' => bcsub((string) $lot->lot_remaining_value, $layerCost, 6),
                ]);
                $allocations[] = ['lot_id' => $lot->id, 'quantity' => $take, 'cost' => $part];
                $cost = bcadd($cost, $part, 6);
                $remaining = bcsub($remaining, $take, 6);
                if (bccomp($remaining, '0', 6) === 0) {
                    break;
                }
            }
            if (bccomp($remaining, '0', 6) > 0) {
                $this->fail('Stock lots do not reconcile to the available balance.');
            }
        }
        if (bccomp($cost, '0', 6) < 0) {
            $this->fail('Inventory cost cannot be negative.');
        }
        $newQuantity = bcadd((string) $balance->quantity, $quantity, 6);
        $newValue = $incoming ? bcadd((string) $balance->value, $cost, 6) : bcsub((string) $balance->value, $cost, 6);
        if (bccomp($newValue, '0', 6) < 0) {
            $this->fail('Inventory value does not reconcile.');
        }
        $stock->date = $date;
        $stock->costing_method = $policy->method;
        $stock->total_cost = $cost;
        $stock->unit_cost = bcdiv($cost, ltrim($quantity, '-'), 8);
        $stock->inventory_value = $newValue;
        $stock->available_quantity = $newQuantity;
        $stock->total_quantity ??= $incoming ? $quantity : '0';
        if ($incoming) {
            $stock->selling_price ??= Product::withTrashed()->findOrFail($stock->product_id)->selling_price;
            $stock->lot_remaining_quantity = $quantity;
            $stock->lot_remaining_value = $cost;
            $stock->lot_number = $stock->lot_number ?: 'LOT-'.Str::ulid();
        }
        DB::table('inventory_balances')->where('id', $balance->id)->update([
            'quantity' => $newQuantity, 'value' => $newValue, 'last_effective_date' => $date,
        ]);
        DB::table('inventory_policies')->where('id', 1)->update(['locked' => true, 'updated_at' => now()]);

        return $allocations;
    }

    public function finish(ProductStock $stock, array $allocations): void
    {
        $inventory = 'inventory:'.$stock->product_id.':'.($stock->product_variant_id ?? 0);
        $incoming = bccomp((string) $stock->quantity, '0', 6) > 0;
        $isSale = in_array($stock->type, ['sale', 'sale_return', 'sale_reversal'], true);
        app(FinancialLedger::class)->pair('product_stocks', $stock->id,
            $incoming ? $inventory : ($isSale ? 'cogs' : 'clearing'),
            $incoming ? ($isSale ? 'cogs' : 'clearing') : $inventory,
            $stock->total_cost, $stock->date);
        foreach ($allocations as $allocation) {
            DB::table('inventory_allocations')->insert($allocation + ['stock_id' => $stock->id]);
        }
        if ($stock->sale_item_id && $stock->type === 'sale') {
            DB::table('sale_items')->where('id', $stock->sale_item_id)->update([
                'cost_total' => $stock->total_cost, 'costing_method' => $stock->costing_method,
            ]);
            DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('source_id', $stock->sale_item_id)
                ->update(['cost' => $stock->total_cost, 'updated_at' => now()]);
        }
        if ($stock->sale_return_item_id) {
            DB::table('sale_return_items')->where('id', $stock->sale_return_item_id)->update(['cost_total' => $stock->total_cost]);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['stock' => $message]);
    }
}
