<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class SaleCreator
{
    /**
     * Create a Sale exactly like POS (stock lock/update + customer balance + optional bank payment).
     *
     * @param  array<int, array{product_id:int, quantity:numeric, unit_price:numeric}>  $items
     */
    public function create(array $items, string|float $subtotal, string|float $discount, string|float $total, string|float $paid, ?int $bankAccountId, ?int $customerId, ?string $note, ?int $createdBy, ?string $date = null, ?string $requestKey = null): Sale
    {
        $date = $date ?: now()->toDateString();
        $items = array_values($items);
        foreach ($items as &$item) {
            $item['quantity'] = DecimalInput::normalize($item['quantity'], 6);
            $item['unit_price'] = DecimalInput::normalize($item['unit_price'], 2);
        }
        unset($item);
        $subtotal = DecimalInput::normalize($subtotal, 2);
        $discount = DecimalInput::normalize($discount, 2);
        $total = DecimalInput::normalize($total, 2);
        $paid = DecimalInput::normalize($paid, 2);
        app(SaleAmounts::class)->validate($items, $subtotal, $discount, $total, $paid);
        if (! $customerId && bccomp((string) $paid, (string) $total, 2) < 0) {
            throw new \LogicException('Walk-in sales require full payment.');
        }
        $requestHash = hash('sha256', json_encode([$items, $subtotal, $discount, $total, $paid, $bankAccountId, $customerId, $note], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($items, $subtotal, $discount, $total, $paid, $bankAccountId, $customerId, $note, $createdBy, $date, $requestKey, $requestHash) {
            DB::table('inventory_policies')->lockForUpdate()->find(1);
            if ($requestKey && ($existing = Sale::withTrashed()->where('request_key', $requestKey)->first())) {
                if ($existing->trashed() || $existing->request_hash !== $requestHash) {
                    throw new \LogicException('This request key belongs to a different or cancelled sale.');
                }

                return $existing;
            }
            $invoiceNumber = $this->generateInvoiceNo();

            $sale = Sale::create([
                'invoice_no' => $invoiceNumber,
                'business_date' => $date,
                'bank_account_id' => $bankAccountId,
                'request_key' => $requestKey, 'request_hash' => $requestHash,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'due' => bcsub($total, $paid, 2),
                'payment_status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
                'note' => $note,
                'created_by' => $createdBy,
            ]);

            $lineSubtotals = app(SaleAmounts::class)->lineSubtotals($items, $subtotal);
            $lineDiscounts = app(SaleAmounts::class)->allocate($lineSubtotals, $discount);
            foreach ($items as $itemIndex => $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                if ($product->isService()) {
                    $saleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'lot_id' => $item['lot_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $lineSubtotals[$itemIndex],
                        'discount_amount' => $lineDiscounts[$itemIndex], 'tax_amount' => 0,
                    ]);
                } else {
                    $currentStock = ProductStock::where('product_id', $item['product_id'])->where('product_variant_id', $item['product_variant_id'] ?? null)
                        ->lockForUpdate()
                        ->orderBy('id', 'desc')
                        ->first();

                    $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                    $afterQuantity = $beforeQuantity - $item['quantity'];

                    if ($afterQuantity < 0) {
                        throw new \Exception("Insufficient stock for product ID: {$item['product_id']}, Available: {$beforeQuantity}, Requested: {$item['quantity']}");
                    }

                    $saleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'lot_id' => $item['lot_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $lineSubtotals[$itemIndex],
                        'discount_amount' => $lineDiscounts[$itemIndex], 'tax_amount' => 0,
                    ]);

                    ProductStock::create([
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'sale_item_id' => $saleItem->id,
                        'quantity' => -$item['quantity'],
                        'available_quantity' => $afterQuantity,
                        'type' => 'sale',
                        'date' => $date,
                        'unit_cost' => $currentStock ? $currentStock->unit_cost : 0,
                        'total_cost' => $currentStock ? ($currentStock->unit_cost * $item['quantity']) : 0,
                        'created_by' => $createdBy,
                    ]);

                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'quantity' => $item['quantity'],
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $afterQuantity,
                        'type' => 'out',
                        'created_by' => $createdBy,
                    ]);
                }
            }

            if ($customerId && $sale->due > 0) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $newBalance = bcadd((string) $customer->balance, (string) $sale->due, 2);
                    if ($customer->credit_limit > 0 && bccomp($newBalance, (string) $customer->credit_limit, 2) > 0) {
                        throw new \Exception("This sale would exceed the customer's credit limit. Current balance: {$customer->balance}, Credit limit: {$customer->credit_limit}, New due: {$sale->due}");
                    }
                    $customer->increment('balance', (float) $sale->due);
                }
            }

            if ($paid > 0) {
                if (! $bankAccountId) {
                    throw new \Exception('Bank account is required when paid amount is greater than 0.');
                }

                $bankAccount = BankAccount::findOrFail($bankAccountId);
                $bankAccount->transactions()->create([
                    'transaction_type' => 'in',
                    'amount' => $paid,
                    'date' => $date,
                    'description' => "Payment received for invoice {$sale->invoice_no}",
                    'created_by' => $createdBy,
                ]);
            }

            return $sale;
        }, 3);
    }

    private function generateInvoiceNo(): string
    {
        $date = date('Ymd');

        $lastSale = Sale::withTrashed()
            ->where('invoice_no', 'like', "INV-{$date}-%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastSale && preg_match('/INV-'.$date.'-(\d+)/', $lastSale->invoice_no, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        while (Sale::withTrashed()->where('invoice_no', 'INV-'.$date.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT))->exists()) {
            $nextSequence++;
        }

        return 'INV-'.$date.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
