<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\InventoryReport;
use App\Services\LegacyInventoryCarryForward;
use App\Services\PurchaseReversalService;
use App\Traits\ManagesStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductStockController extends Controller
{
    use ManagesStock;

    public function index(Request $request)
    {
        app(LegacyInventoryCarryForward::class)->ensure();
        $request->validate(['per_page' => 'nullable|integer|min:1|max:100', 'sort_order' => 'nullable|in:asc,desc']);
        $query = Product::query()->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'service'))
            ->whereHas('productStocks')->with('productStocks')
            ->when($request->search, fn ($q, $search) => $q->where(fn ($sub) => $sub->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%')));
        $sort = $request->input('sort_field') === 'product_name' ? 'name' : 'id';
        $stocks = $query->orderBy($sort, $request->input('sort_order', 'asc'))->paginate($request->integer('per_page', 15))->withQueryString()
            ->through(function ($product) {
                $balances = DB::table('inventory_balances')->where('product_id', $product->id)->get();
                $calculator = app(InventoryReport::class);
                $quantity = $calculator->sum($balances, 'quantity');
                $value = $calculator->sum($balances, 'value');
                $purchases = $product->productStocks->where('type', 'purchase');
                $last = $product->productStocks->sortByDesc('id')->first();

                return [
                    'id' => $last->id, 'product' => $product->only(['id', 'name', 'sku', 'alert_quantity']),
                    'quantity' => $quantity, 'total_purchased' => $calculator->sum($purchases, 'quantity'),
                    'average_unit_cost' => bccomp($quantity, '0', 6) > 0 ? bcdiv($value, $quantity, 6) : '0',
                    'current_stock_value' => $value, 'total_purchase_cost' => $calculator->sum($purchases, 'total_cost'),
                    'created_by' => $last->createdBy?->name ?? 'N/A', 'created_at' => $last->created_at,
                    'stock_status' => bccomp($quantity, '0', 6) <= 0 ? 'out' : ((float) $quantity <= $product->alert_quantity ? 'low' : 'in'),
                    'lots' => $product->productStocks
                        ->filter(fn ($lot) => (float) $lot->lot_remaining_quantity > 0)
                        ->sortBy(fn ($lot) => ($lot->date ?? '').'-'.str_pad((string) $lot->id, 10, '0', STR_PAD_LEFT))
                        ->values()
                        ->map(fn ($lot) => [
                            'id' => $lot->id,
                            'lot_number' => $lot->lot_number,
                            'date' => $lot->date,
                            'received_quantity' => $lot->type === 'legacy_opening' ? $lot->total_quantity : $lot->quantity,
                            'remaining_quantity' => $lot->lot_remaining_quantity,
                            'purchase_cost' => $lot->unit_cost,
                            'selling_price' => $lot->selling_price ?? $product->selling_price,
                        ]),
                ];
            });
        $balances = DB::table('inventory_balances')->get();
        $calculator = app(InventoryReport::class);
        $quantities = $balances->groupBy('product_id')->map(fn ($rows) => $calculator->sum($rows, 'quantity'));
        $low = Product::whereIn('id', $quantities->keys())->get()->filter(fn ($p) => (float) $quantities[$p->id] > 0 && (float) $quantities[$p->id] <= $p->alert_quantity)->count();

        return Inertia::render('Admin/ProductStocks/Index', [
            'stocks' => $stocks, 'summary' => [
                'total_products' => $quantities->count(), 'total_quantity' => $calculator->sum($balances, 'quantity'),
                'total_value' => $calculator->sum($balances, 'value'), 'low_stock_items' => $low,
            ], 'filters' => $request->only(['search', 'sort_field', 'sort_order', 'per_page']),
        ]);
    }

    public function create()
    {
        $products = Product::all();
        $lotDefaults = Product::fifoLotDefaults($products);
        $products = $products->map(function ($product) use ($lotDefaults) {
            $latestStock = ProductStock::where('product_id', $product->id)->latest('id')->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->availableQuantity(),
                'selling_price' => (float) $product->selling_price,
                'next_lot_number' => $lotDefaults[$product->id]['next_lot_number'],
                'previous_selling_price' => (float) ($lotDefaults[$product->id]['previous_selling_price'] ?? $product->selling_price ?? 0),
                'last_unit_cost' => $latestStock ? (float) $latestStock->unit_cost : (float) ($product->cost_price ?? 0),
            ];
        });

        $bankAccounts = BankAccount::where('status', true)
            ->select('id', 'account_name', 'bank_name', 'account_number', 'current_balance')
            ->get();

        $suppliers = Supplier::where('status', 'active')
            ->orWhereNull('status')
            ->select('id', 'name', 'company_name', 'phone', 'current_balance')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/ProductStocks/Create', [
            'products' => $products,
            'bankAccounts' => $bankAccounts,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $isCredit = filter_var($request->input('is_credit'), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'total_cost' => 'required|numeric|min:0.01',
            'date' => 'required|date|before_or_equal:today',
            'note' => 'nullable|string',
            'lot_number' => 'nullable|string|max:100',
            'selling_price' => 'nullable|numeric|min:0',
            'is_credit' => 'nullable|boolean',
        ];

        if ($isCredit) {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        } else {
            $rules['bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();
            $policy = DB::table('inventory_policies')->lockForUpdate()->find(1);
            $fifo = (($policy?->method ?? 'fifo') === 'fifo');

            $bankAccount = null;
            if (! $isCredit) {
                // Check bank balance
                $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
                if (bccomp((string) $bankAccount->current_balance, (string) $validated['total_cost'], 4) < 0) {
                    throw new \Exception('নির্বাচিত ব্যাংক অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই (Insufficient bank balance)');
                }
            }

            // Calculate unit cost precisely
            $unitCost = bcdiv((string) $validated['total_cost'], (string) $validated['quantity'], 6);

            // Get current available quantity with row lock to prevent race conditions
            $currentStock = ProductStock::where('product_id', $validated['product_id'])
                ->whereNull('product_variant_id')
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $currentAvailableQuantity = $currentStock ? $currentStock->available_quantity : 0;
            $newAvailableQuantity = bcadd((string) $currentAvailableQuantity, (string) $validated['quantity'], 6);

            // Convert date input to datetime for created_at
            $stockDate = Carbon::parse($validated['date'])->startOfDay();

            $supplier = null;
            $note = $validated['note'] ?? null;
            $purchaseNumber = null;
            if ($isCredit) {
                $supplier = Supplier::findOrFail($validated['supplier_id']);
                $purchaseNumber = 'PUR-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -4));
                $creditPrefix = "বাকিতে ক্রয় (Credit Purchase) | সরবরাহকারী: {$supplier->name} | Ref #{$purchaseNumber}";
                $note = $note ? "{$creditPrefix} | {$note}" : $creditPrefix;
            }

            // Create stock entry with custom created_at timestamp
            $stock = new ProductStock([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_quantity' => $validated['quantity'],
                'available_quantity' => $newAvailableQuantity,
                'total_cost' => $validated['total_cost'],
                'unit_cost' => $unitCost,
                'lot_number' => $fifo ? ($validated['lot_number'] ?? null) : null,
                'selling_price' => $validated['selling_price'] ?? null,
                'type' => 'purchase',
                'date' => $validated['date'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'note' => $note,
                'created_by' => Auth::id(),
            ]);

            $stock->created_at = $stockDate;
            $stock->updated_at = $stockDate;
            $stock->save();

            // Record stock movement with matching timestamp
            $movement = new StockMovement([
                'product_id' => $validated['product_id'],
                'reference_type' => 'purchase',
                'reference_id' => $stock->id,
                'quantity' => $validated['quantity'],
                'before_quantity' => $currentAvailableQuantity,
                'after_quantity' => $newAvailableQuantity,
                'type' => 'in',
                'created_by' => Auth::id(),
            ]);

            $movement->created_at = $stockDate;
            $movement->updated_at = $stockDate;
            $movement->save();

            if ($isCredit && $supplier) {
                // Update supplier payable balance (we owe the supplier)
                $supplier->increment('current_balance', $validated['total_cost']);

                // Create Purchase record for accounting and ledger completeness
                $purchase = Purchase::create([
                    'purchase_number' => $purchaseNumber,
                    'supplier_id' => $supplier->id,
                    'bank_account_id' => null,
                    'purchase_date' => $validated['date'],
                    'subtotal' => $validated['total_cost'],
                    'total_amount' => $validated['total_cost'],
                    'paid_amount' => 0,
                    'due_amount' => $validated['total_cost'],
                    'payment_status' => 'due',
                    'purchase_status' => 'received',
                    'note' => $note,
                    'created_by' => Auth::id(),
                ]);

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $validated['product_id'],
                    'purchase_price' => $unitCost,
                    'selling_price' => $validated['selling_price'] ?? null,
                    'quantity' => $validated['quantity'],
                    'subtotal' => $validated['total_cost'],
                ]);
                $stock->purchase_item_id = $purchaseItem->id;
                $stock->save();
            } else {
                // Cash/Bank Payment (Observer automatically deducts from bank account balance)
                $transaction = new BankTransaction([
                    'bank_account_id' => $validated['bank_account_id'],
                    'transaction_type' => 'out',
                    'amount' => $validated['total_cost'],
                    'description' => "Stock purchase for product ID: {$validated['product_id']}",
                    'date' => $stockDate,
                    'created_by' => Auth::id(),
                ]);

                $transaction->created_at = $stockDate;
                $transaction->updated_at = $stockDate;
                $transaction->save();
            }

            $this->updateProductWeightedAverageCost($validated['product_id']);
            if (! $fifo && ! empty($validated['selling_price']) && $validated['selling_price'] > 0) {
                Product::whereKey($validated['product_id'])->update(['selling_price' => $validated['selling_price']]);
            }

            DB::commit();

            $successMsg = $isCredit
                ? 'বাকিতে স্টক সফলভাবে যুক্ত করা হয়েছে এবং সরবরাহকারীর বকেয়া হালনাগাদ করা হয়েছে।'
                : 'স্টক সফলভাবে যুক্ত হয়েছে এবং ব্যাংক লেনদেন সম্পন্ন হয়েছে।';

            return redirect()->route('admin.product-stocks.index')
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'ত্রুটি: '.$e->getMessage());
        }
    }

    private function updateProductWeightedAverageCost($productId)
    {
        app(PurchaseReversalService::class)->updateProductWeightedAverageCost($productId);
    }

    public function destroy($id, PurchaseReversalService $reversalService)
    {
        try {
            $stock = ProductStock::with('product')->findOrFail($id);
            $result = $reversalService->reverseStockEntry($stock);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getStockHistory($productId)
    {
        $history = ProductStock::with(['createdBy'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'type' => $stock->type,
                    'quantity' => $stock->quantity,
                    'received_quantity' => $stock->type === 'legacy_opening' ? $stock->total_quantity : $stock->quantity,
                    'unit_cost' => $stock->unit_cost,
                    'total_cost' => $stock->total_cost,
                    'lot_number' => $stock->lot_number, 'lot_remaining_quantity' => $stock->lot_remaining_quantity,
                    'selling_price' => $stock->selling_price, 'costing_method' => $stock->costing_method,
                    'inventory_value' => $stock->inventory_value, 'date' => $stock->date,
                    'variant_id' => $stock->product_variant_id,
                    'available_quantity' => $stock->available_quantity,
                    'note' => $stock->note,
                    'created_by' => $stock->createdBy?->name ?? 'N/A',
                    'created_at' => $stock->created_at->format('d M Y, h:i A'),
                    'can_delete' => $stock->type === 'purchase' && $stock->costing_method !== null && Auth::user()->role->name === 'Admin',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
