<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseReversalService
{
    public function reversePurchase(Purchase $purchase): array
    {
        return DB::transaction(function () use ($purchase) {
            DB::table('inventory_policies')->lockForUpdate()->find(1);
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if (bccomp((string) $purchase->paid_amount, '0', 2) > 0 || $purchase->payments()->exists()) {
                throw new \LogicException('A paid purchase requires a supplier return and refund/credit document.');
            }
            $stocks = ProductStock::whereIn('purchase_item_id', $purchase->items()->select('id'))->where('type', 'purchase')->get();
            if ($stocks->count() !== $purchase->items()->count()) {
                throw new \LogicException('Purchase stock links must be reconciled before cancellation.');
            }
            foreach ($stocks->sortByDesc('id') as $stock) {
                $this->reverseReceipt($stock);
            }
            if ($purchase->supplier_id) {
                Supplier::whereKey($purchase->supplier_id)->decrement('current_balance', $purchase->due_amount);
            }
            // Preserve source items and append reversal entries; historical statements remain reproducible.
            $purchase->delete();

            return ['success' => true, 'message' => 'Purchase cancelled with linked stock and accounting reversals.'];
        });
    }

    public function reverseStockEntry(ProductStock $stock): array
    {
        return DB::transaction(function () use ($stock) {
            DB::table('inventory_policies')->lockForUpdate()->find(1);
            if ($stock->purchase_item_id) {
                $purchase = Purchase::whereHas('items', fn ($q) => $q->whereKey($stock->purchase_item_id))->firstOrFail();

                return $this->reversePurchase($purchase);
            }
            // Cash receipts need an explicit account link; never identify money by amount/date/text.
            if (! $stock->bank_account_id) {
                throw new \LogicException('This legacy cash receipt needs a verified bank account link before cancellation.');
            }
            $this->reverseReceipt($stock);
            BankTransaction::create([
                'bank_account_id' => $stock->bank_account_id, 'transaction_type' => 'in',
                'amount' => $stock->total_cost, 'category' => 'stock_refund', 'date' => now()->toDateString(),
                'description' => 'Refund for cancelled stock lot #'.$stock->id, 'created_by' => auth()->id(),
            ]);

            return ['success' => true, 'message' => 'Receipt cancelled and cash refund recorded.'];
        });
    }

    private function reverseReceipt(ProductStock $stock): void
    {
        ProductStock::create([
            'product_id' => $stock->product_id, 'product_variant_id' => $stock->product_variant_id,
            'source_stock_id' => $stock->id, 'quantity' => bcmul((string) $stock->quantity, '-1', 6),
            'total_cost' => 0, 'unit_cost' => 0, 'type' => 'purchase_reversal',
            'date' => now()->toDateString(), 'note' => 'Cancellation of receipt #'.$stock->id,
            'created_by' => auth()->id() ?? $stock->created_by,
        ]);
    }

    public function updateProductWeightedAverageCost(int $productId): void
    {
        $balances = DB::table('inventory_balances')->where('product_id', $productId)->get();
        $report = app(InventoryReport::class);
        $quantity = $report->sum($balances, 'quantity');
        $value = $report->sum($balances, 'value');
        if (bccomp($quantity, '0', 6) > 0) {
            Product::whereKey($productId)->update(['cost_price' => bcdiv($value, $quantity, 6)]);
        }
    }
}
