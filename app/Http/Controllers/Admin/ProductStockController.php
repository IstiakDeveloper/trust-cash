<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\PurchaseReversalService;
use App\Traits\ManagesStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductStockController extends Controller
{
    use ManagesStock;

    public function index(Request $request)
    {
        $query = ProductStock::with(['createdBy'])
            ->select(
                'product_stocks.*',
                'products.name as product_name',
                'products.sku',
                'products.alert_quantity',
                // Get latest available quantity from any type
                DB::raw('(
                    SELECT COALESCE(available_quantity, 0)
                    FROM product_stocks ps2
                    WHERE ps2.product_id = product_stocks.product_id
                    AND ps2.deleted_at IS NULL
                    ORDER BY id DESC LIMIT 1
                ) as current_available'),
                // Calculate total purchase quantity and value (only from purchase type)
                DB::raw('(
                    SELECT COALESCE(SUM(quantity), 0)
                    FROM product_stocks ps2
                    WHERE ps2.product_id = product_stocks.product_id
                    AND ps2.type = "purchase"
                    AND ps2.deleted_at IS NULL
                ) as total_purchase_quantity'),
                DB::raw('(
                    SELECT COALESCE(SUM(quantity * unit_cost), 0)
                    FROM product_stocks ps2
                    WHERE ps2.product_id = product_stocks.product_id
                    AND ps2.type = "purchase"
                    AND ps2.deleted_at IS NULL
                ) as total_purchase_cost')
            )
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->whereNull('product_stocks.deleted_at')
            ->whereIn('product_stocks.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->whereNull('deleted_at')
                    ->groupBy('product_id');
            });

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sort_field', 'product_id');
        $sortOrder = $request->input('sort_order', 'asc');

        if ($sortField === 'product_name') {
            $query->orderBy('products.name', $sortOrder);
        } else {
            $query->orderBy('product_stocks.'.$sortField, $sortOrder);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $paginatedResults = $query->paginate($perPage);

        // Transform the items manually
        $transformedItems = collect($paginatedResults->items())->map(function ($stock) {
            // Calculate weighted average unit cost from purchases only
            $averageUnitCost = $stock->total_purchase_quantity > 0
                ? bcdiv($stock->total_purchase_cost, $stock->total_purchase_quantity, 6)
                : 0;

            // Calculate current stock value using weighted average
            $currentStockValue = bcmul($stock->current_available ?? 0, $averageUnitCost, 6);

            return [
                'id' => $stock->id,
                'product' => [
                    'id' => $stock->product_id,
                    'name' => $stock->product_name,
                    'sku' => $stock->sku,
                    'alert_quantity' => $stock->alert_quantity,
                ],
                'quantity' => $stock->current_available ?? 0,
                'total_purchased' => $stock->total_purchase_quantity,
                'average_unit_cost' => round($averageUnitCost, 2),
                'current_stock_value' => round($currentStockValue, 2),
                'total_purchase_cost' => round($stock->total_purchase_cost, 2),
                'created_by' => $stock->createdBy?->name ?? 'N/A',
                'created_at' => $stock->created_at,
                'stock_status' => ($stock->current_available ?? 0) <= 0 ? 'out' : (($stock->current_available ?? 0) <= $stock->alert_quantity ? 'low' : 'in'),
            ];
        })->toArray();

        // Create new paginator with transformed data
        $stocks = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedItems,
            $paginatedResults->total(),
            $paginatedResults->perPage(),
            $paginatedResults->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Summary query
        $summaryQuery = DB::table('product_stocks as ps')
            ->whereNull('ps.deleted_at')
            ->select([
                DB::raw('COUNT(DISTINCT ps.product_id) as total_products'),
                // Get total current quantity from latest entries
                DB::raw('COALESCE(SUM(
                    CASE WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        WHERE deleted_at IS NULL
                        GROUP BY product_id
                    )
                    THEN ps.available_quantity
                    ELSE 0
                    END
                ), 0) as total_quantity'),
                // Calculate total stock value using weighted averages from purchases only
                DB::raw('COALESCE(SUM(
                    CASE WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        WHERE deleted_at IS NULL
                        GROUP BY product_id
                    )
                    THEN (
                        ps.available_quantity * (
                            SELECT
                                CASE
                                    WHEN SUM(quantity) > 0
                                    THEN SUM(quantity * unit_cost) / SUM(quantity)
                                    ELSE 0
                                END
                            FROM product_stocks ps2
                            WHERE ps2.product_id = ps.product_id
                            AND ps2.type = "purchase"
                            AND ps2.deleted_at IS NULL
                        )
                    )
                    ELSE 0
                    END
                ), 0) as total_value'),
            ])
            ->first();

        // Low stock calculation
        $lowStockItems = DB::table('product_stocks as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->whereNull('ps.deleted_at')
            ->whereIn('ps.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->whereNull('deleted_at')
                    ->groupBy('product_id');
            })
            ->where('ps.available_quantity', '<=', DB::raw('p.alert_quantity'))
            ->where('ps.available_quantity', '>', 0)
            ->count();

        return Inertia::render('Admin/ProductStocks/Index', [
            'stocks' => $stocks,
            'summary' => [
                'total_products' => $summaryQuery->total_products,
                'total_quantity' => round($summaryQuery->total_quantity, 2),
                'total_value' => round($summaryQuery->total_value, 2),
                'low_stock_items' => $lowStockItems,
            ],
            'filters' => $request->only(['search', 'sort_field', 'sort_order', 'per_page']),
        ]);
    }

    public function create()
    {
        $products = Product::all()->map(function ($product) {
            $latestStock = ProductStock::where('product_id', $product->id)->latest('id')->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $latestStock ? (float) $latestStock->available_quantity : 0,
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
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $currentAvailableQuantity = $currentStock ? $currentStock->available_quantity : 0;
            $newAvailableQuantity = bcadd((string) $currentAvailableQuantity, (string) $validated['quantity'], 6);

            // Convert date input to datetime for created_at
            $stockDate = \Carbon\Carbon::parse($validated['date'])->startOfDay();

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
                'type' => 'purchase',
                'date' => $validated['date'],
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

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $validated['product_id'],
                    'purchase_price' => $unitCost,
                    'selling_price' => null,
                    'quantity' => $validated['quantity'],
                    'subtotal' => $validated['total_cost'],
                ]);
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

            // Update product's weighted average cost
            $this->updateProductWeightedAverageCost($validated['product_id']);

            // Update product's cost_price
            $product = Product::find($validated['product_id']);
            if ($product && (float) $unitCost > 0) {
                $product->update(['cost_price' => round((float) $unitCost, 2)]);
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
        $product = Product::findOrFail($productId);

        $stocks = $product->productStocks()
            ->select(
                DB::raw('COALESCE(SUM(CAST(quantity AS DECIMAL(15,6))), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(CAST(quantity AS DECIMAL(15,6)) * CAST(unit_cost AS DECIMAL(15,6))), 0) as total_value')
            )
            ->where('quantity', '>', 0)
            ->first();

        if ($stocks->total_quantity > 0) {
            $weightedAverageCost = bcdiv($stocks->total_value, $stocks->total_quantity, 6);
            $product->update(['cost_price' => $weightedAverageCost]);
        }
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
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'type' => $stock->type,
                    'quantity' => $stock->quantity,
                    'unit_cost' => $stock->unit_cost,
                    'total_cost' => $stock->total_cost,
                    'available_quantity' => $stock->available_quantity,
                    'note' => $stock->note,
                    'created_by' => $stock->createdBy?->name ?? 'N/A',
                    'created_at' => $stock->created_at->format('d M Y, h:i A'),
                    'can_delete' => $stock->type === 'purchase' && Auth::user()->role->name === 'Admin',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
