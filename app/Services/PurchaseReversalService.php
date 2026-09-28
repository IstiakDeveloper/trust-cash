<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseReversalService
{
    /**
     * Reverse and delete a purchase from the Purchase model.
     *
     * @param Purchase $purchase
     * @return array
     * @throws Exception
     */
    public function reversePurchase(Purchase $purchase): array
    {
        return DB::transaction(function () use ($purchase) {
            // 1. Check if any payments were already made for this purchase
            if ($purchase->paid_amount > 0 || $purchase->payments()->exists()) {
                throw new Exception("এই ক্রয়ের বিপরীতে ইতিমধ্যে ৳" . number_format($purchase->paid_amount, 2) . " পরিশোধ করা হয়েছে। সরাসরি ক্রয় চালান মুছে ফেলা সম্ভব নয়। লেনদেন সুরক্ষিত রাখতে ক্রয় ফেরত (Purchase Return) ব্যবহার করুন।");
            }

            // 2. Check if any items have already been sold in POS/sales
            $items = $purchase->items()->with('product')->get();
            foreach ($items as $item) {
                $this->validateStockAvailable($item->product_id, (float) $item->quantity, $item->product?->name);
            }

            // 3. Check supplier balance integrity (ensure reversing doesn't make supplier balance negative)
            if ($purchase->supplier_id && $purchase->due_amount > 0) {
                $supplier = Supplier::find($purchase->supplier_id);
                if ($supplier) {
                    if (bccomp((string) $supplier->current_balance, (string) $purchase->due_amount, 4) < 0) {
                        throw new Exception("সাপ্লায়ার '{$supplier->name}' এর বর্তমান মোট দেনা (৳" . number_format($supplier->current_balance, 2) . ") এই চালানের বকেয়া (৳" . number_format($purchase->due_amount, 2) . ") এর চেয়ে কম! অন্য পেমেন্টের কারণে দেনা সমন্বিত হওয়ায় এটি বাতিল করলে দেনা ঋণাত্মক হয়ে যাবে।");
                    }
                    $supplier->decrement('current_balance', $purchase->due_amount);
                }
            }

            // 4. Reverse Stock and StockMovements for each item
            foreach ($items as $item) {
                // Find matching product stock entries
                $stockEntries = ProductStock::where('product_id', $item->product_id)
                    ->where(function ($q) use ($purchase, $item) {
                        $q->where('note', 'like', "%{$purchase->purchase_number}%")
                          ->orWhere(function ($sub) use ($purchase, $item) {
                              $sub->whereDate('date', $purchase->purchase_date)
                                  ->where('quantity', $item->quantity);
                          });
                    })
                    ->get();

                foreach ($stockEntries as $stock) {
                    $this->revertProductStockRow($stock);
                }

                // Delete StockMovement linked to this purchase
                StockMovement::where(function ($q) use ($purchase, $item) {
                    $q->where('reference_type', Purchase::class)
                      ->where('reference_id', $purchase->id)
                      ->where('product_id', $item->product_id);
                })->orWhere(function ($q) use ($stockEntries) {
                    $q->where('reference_type', 'purchase')
                      ->whereIn('reference_id', $stockEntries->pluck('id'));
                })->delete();

                // Recalculate weighted average cost
                $this->updateProductWeightedAverageCost($item->product_id);
            }

            // 5. Delete Purchase and PurchaseItems
            $purchaseNumber = $purchase->purchase_number;
            $purchase->items()->delete();
            $purchase->delete();

            return [
                'success' => true,
                'message' => "ক্রয় চালান #{$purchaseNumber} সফলভাবে বাতিল করা হয়েছে এবং স্টক ও দেনা সমন্বয় করা হয়েছে।"
            ];
        });
    }

    /**
     * Reverse and delete a stock entry from the ProductStock model.
     *
     * @param ProductStock $stock
     * @return array
     * @throws Exception
     */
    public function reverseStockEntry(ProductStock $stock): array
    {
        return DB::transaction(function () use ($stock) {
            if ($stock->type !== 'purchase') {
                throw new Exception('শুধুমাত্র ক্রয়কৃত (Purchase) স্টক এন্ট্রি মুছে ফেলা সম্ভব।');
            }

            $product = $stock->product ?? Product::find($stock->product_id);
            $productName = $product?->name ?? "Product #{$stock->product_id}";

            // 1. Check if this product has enough available stock left (not sold yet)
            $this->validateStockAvailable($stock->product_id, (float) $stock->quantity, $productName);

            // 2. Identify if there's a linked Purchase record
            $purchase = null;
            if (preg_match('/(PUR-\d+-[A-Z0-9]+)/', $stock->note ?? '', $matches)) {
                $purchase = Purchase::where('purchase_number', $matches[1])->first();
            }

            if (!$purchase) {
                // Try to find purchase by item matching
                $purchase = Purchase::whereDate('purchase_date', $stock->date ?? $stock->created_at->toDateString())
                    ->whereHas('items', function ($q) use ($stock) {
                        $q->where('product_id', $stock->product_id)
                          ->where('quantity', $stock->quantity);
                    })
                    ->first();
            }

            // 3. If credit purchase is linked, validate and revert it
            if ($purchase) {
                // If payment was made on this purchase, block deletion
                if ($purchase->paid_amount > 0 || $purchase->payments()->exists()) {
                    throw new Exception("এই স্টকের সাথে যুক্ত ক্রয় চালানে (Invoice #{$purchase->purchase_number}) ইতিমধ্যে টাকা পরিশোধ করা হয়েছে। সরাসরি স্টক মুছে ফেলা যাবে না।");
                }

                // Check supplier balance
                if ($purchase->supplier_id && $purchase->due_amount > 0) {
                    $supplier = Supplier::find($purchase->supplier_id);
                    if ($supplier) {
                        if (bccomp((string) $supplier->current_balance, (string) $purchase->due_amount, 4) < 0) {
                            throw new Exception("সাপ্লায়ার '{$supplier->name}' এর বর্তমান মোট দেনা (৳" . number_format($supplier->current_balance, 2) . ") এই চালানের বকেয়া (৳" . number_format($purchase->due_amount, 2) . ") এর চেয়ে কম! ব্যালেন্স ঋণাত্মক হওয়া ঠেকাতে এটি ডিলিট করা স্থগিত করা হলো।");
                        }
                        $supplier->decrement('current_balance', $purchase->due_amount);
                    }
                }

                // Delete linked purchase items and purchase
                $purchase->items()->where('product_id', $stock->product_id)->delete();
                if ($purchase->items()->count() === 0) {
                    $purchase->delete();
                }
            } else {
                // Check if there was a cash/bank transaction for this stock
                $bankTransaction = BankTransaction::where('transaction_type', 'out')
                    ->where('amount', $stock->total_cost)
                    ->where('description', 'like', "%Stock purchase for product ID: {$stock->product_id}%")
                    ->whereDate('date', $stock->created_at->toDateString())
                    ->first();

                if ($bankTransaction) {
                    // Observer automatically refunds the bank account upon delete
                    $bankTransaction->delete();
                }
            }

            // 4. Revert the ProductStock and its running available_quantity
            $this->revertProductStockRow($stock);

            // 5. Delete related stock movement
            StockMovement::where('reference_type', 'purchase')
                ->where('reference_id', $stock->id)
                ->delete();

            // 6. Update product's average cost
            $this->updateProductWeightedAverageCost($stock->product_id);

            return [
                'success' => true,
                'message' => 'স্টক এন্ট্রি সফলভাবে বাতিল করা হয়েছে এবং সংশ্লিষ্ট হিসাব সমন্বয় করা হয়েছে।'
            ];
        });
    }

    /**
     * Check that current available quantity is at least equal to quantity being removed.
     */
    protected function validateStockAvailable(int $productId, float $quantityToRemove, ?string $productName = null): void
    {
        $latestStock = ProductStock::where('product_id', $productId)
            ->orderByDesc('id')
            ->first();

        $currentAvailable = $latestStock ? (float) $latestStock->available_quantity : 0;

        if ($currentAvailable < $quantityToRemove) {
            $name = $productName ?? "Product #{$productId}";
            $shortage = round($quantityToRemove - $currentAvailable, 2);
            throw new Exception("পণ্য '{$name}' এর এই চালানের পণ্য ইতিমধ্যে বিক্রি হয়ে গেছে! বর্তমান অবশিষ্ট স্টক রয়েছে " . round($currentAvailable, 2) . " টি (ঘাটতি: {$shortage} টি)। বিক্রিত পণ্য ফেরত/বাতিল না করে এই স্টক ডিলিট করা যাবে না।");
        }
    }

    /**
     * Safely delete a ProductStock row and adjust running available_quantity on all subsequent rows.
     */
    protected function revertProductStockRow(ProductStock $stock): void
    {
        $productId = $stock->product_id;
        $qty = $stock->quantity;

        // Decrement available_quantity on all subsequent rows for this product
        ProductStock::where('product_id', $productId)
            ->where('id', '>', $stock->id)
            ->decrement('available_quantity', $qty);

        // Delete the stock entry
        $stock->delete();
    }

    /**
     * Recalculate weighted average unit cost for product.
     */
    public function updateProductWeightedAverageCost(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $stocks = $product->productStocks()
            ->select(
                DB::raw('COALESCE(SUM(CAST(quantity AS DECIMAL(15,6))), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(CAST(quantity AS DECIMAL(15,6)) * CAST(unit_cost AS DECIMAL(15,6))), 0) as total_value')
            )
            ->where('quantity', '>', 0)
            ->first();

        if ($stocks && $stocks->total_quantity > 0) {
            $weightedAverageCost = bcdiv((string) $stocks->total_value, (string) $stocks->total_quantity, 6);
            $product->update(['cost_price' => round((float) $weightedAverageCost, 2)]);
        }
    }
}
