<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use NumberFormatter;

class PosController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Pos/Index', [
            'customers' => Customer::active()
                ->select('id', 'name', 'phone', 'balance', 'credit_limit')
                ->get(),
            'bankAccounts' => BankAccount::active()
                ->select('id', 'account_name', 'bank_name', 'current_balance')
                ->get(),
            'categories' => Category::where('status', true)
                ->select('id', 'name')
                ->get(),
        ]);
    }

    public function products()
    {
        return Product::with([
                'productStocks',
                'primaryImage',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('status', true)
            ->get()
            ->map(function ($product) {
                $latestStock = $product->productStocks->sortByDesc('id')->first();
                $img = $product->primaryImage ?: $product->images->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
                    'image' => $img ? $img->image : null,
                    'image_url' => $img
                        ? ('/storage/'.ltrim($img->image, '/'))
                        : null,
                ];
            });
    }

    public function productsByCategory(Request $request)
    {
        $query = Product::with([
                'productStocks',
                'primaryImage',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return $query->get()->map(function ($product) {
            $latestStock = $product->productStocks->sortByDesc('id')->first();
            $img = $product->primaryImage ?: $product->images->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
                'image' => $img ? $img->image : null,
                'image_url' => $img
                    ? ('/storage/'.ltrim($img->image, '/'))
                    : null,
            ];
        });
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with([
                'productStocks',
                'primaryImage',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('status', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        return $query->take(10)->get()->map(function ($product) {
            $latestStock = $product->productStocks->sortByDesc('id')->first();
            $img = $product->primaryImage ?: $product->images->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
                'image' => $img ? $img->image : null,
                'image_url' => $img
                    ? ('/storage/'.ltrim($img->image, '/'))
                    : null,
            ];
        });
    }

    public function searchByBarcode(Request $request)
    {
        $barcode = $request->input('barcode');
        $product = Product::with(['productStocks', 'primaryImage'])
            ->where('status', true)
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)->orWhere('sku', $barcode);
            })
            ->first();

        if (! $product) {
            return response()->json([]);
        }

        $latestStock = $product->productStocks->sortByDesc('id')->first();
        $img = $product->primaryImage;
        if (! $img) {
            $product->load(['images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')]);
            $img = $product->images->first();
        }

        return response()->json([[
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'selling_price' => $product->selling_price,
            'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
            'image' => $img ? $img->image : null,
            'image_url' => $img
                ? ('/storage/'.ltrim($img->image, '/'))
                : null,
        ]]);
    }

    public function store(Request $request)
    {
        Log::info('POS Store Request: '.json_encode($request->all()));

        try {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'discount' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'paid' => 'required|numeric|min:0',
                'bank_account_id' => 'required|exists:bank_accounts,id',
                'customer_id' => 'nullable|exists:customers,id',
            ]);

            Log::info('Validation passed');

            $createdBy = Auth::id();
            if ($createdBy === null) {
                throw new \Exception('You must be logged in to process a sale.');
            }

            DB::beginTransaction();
            Log::info('Transaction started');

            $saleDate = now()->toDateString();
            $date = date('Ymd');

            $total = (float) $request->total;
            $rawPaid = (float) $request->paid;

            // Walk-in customer cannot have due/credit sale
            if (empty($request->customer_id)) {
                if ($rawPaid < $total) {
                    throw new \Exception('সাধারণ (Walk-in) কাস্টমারের ক্ষেত্রে বাকি বিক্রয় সম্ভব নয়। সম্পূর্ণ টাকা নগদ পরিশোধ আবশ্যক।');
                }
                $paid = $total;
                $due = 0.0;
            } else {
                $paid = min($total, max(0.0, $rawPaid));
                $due = max(0.0, $total - $paid);
            }

            // Create sale with automatic sequence detection & duplicate skip
            $sale = null;
            $maxAttempts = 50;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $invoiceNumber = $this->generateUniqueInvoiceNumber($date);

                try {
                    $sale = Sale::create([
                        'invoice_no' => $invoiceNumber,
                        'customer_id' => $request->customer_id,
                        'subtotal' => $request->subtotal,
                        'discount' => $request->discount,
                        'total' => $total,
                        'paid' => $paid,
                        'due' => $due,
                        'payment_status' => $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
                        'note' => $request->note ?? null,
                        'created_by' => $createdBy,
                    ]);

                    break; // Successfully created
                } catch (\Illuminate\Database\QueryException $e) {
                    // Check for MySQL 1062 duplicate key error: auto-skip to next number
                    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                        Log::warning("Duplicate invoice {$invoiceNumber} detected, auto-skipping to next sequence number...");
                        continue;
                    }
                    throw $e;
                }
            }

            if (!$sale) {
                throw new \Exception('ইনভয়েস নম্বর তৈরি করতে ব্যর্থ হয়েছে, অনুগ্রহ করে আবার চেষ্টা করুন।');
            }

            Log::info('Sale created with ID: '.$sale->id.' and Invoice: '.$sale->invoice_no);

            // Process each item
            foreach ($request->items as $index => $item) {
                Log::info("Processing item {$index}: ".json_encode($item));

                // Check stock before attempting to create the sale item
                // Use lockForUpdate to prevent race conditions
                $currentStock = ProductStock::where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                $afterQuantity = $beforeQuantity - $item['quantity'];

                Log::info("Stock check: before={$beforeQuantity}, after={$afterQuantity}");

                // Check if stock is available
                if ($afterQuantity < 0) {
                    throw new \Exception("Insufficient stock for product ID: {$item['product_id']}, Available: {$beforeQuantity}, Requested: {$item['quantity']}");
                }

                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                Log::info("Sale item created for product ID: {$item['product_id']}");

                // Create new stock record
                ProductStock::create([
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'], // negative for sales
                    'available_quantity' => $afterQuantity,
                    'type' => 'sale',
                    'date' => $saleDate,
                    'unit_cost' => $currentStock ? $currentStock->unit_cost : 0,
                    'total_cost' => $currentStock ? ($currentStock->unit_cost * $item['quantity']) : 0,
                    'created_by' => $createdBy,
                ]);

                Log::info("Product stock updated for product ID: {$item['product_id']}");

                // Record stock movement
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

                Log::info("Stock movement recorded for product ID: {$item['product_id']}");
            }

            // Update customer balance if needed
            if ($request->customer_id && $sale->due > 0) {
                $customer = Customer::find($request->customer_id);

                if ($customer) {
                    Log::info("Checking customer credit limit: current balance={$customer->balance}, credit limit={$customer->credit_limit}, new due={$sale->due}");

                    $newBalance = (float) $customer->balance + (float) $sale->due;
                    $creditLimit = (float) ($customer->credit_limit ?? 0);

                    // Check credit limit only if credit limit is actively configured (> 0)
                    if ($creditLimit > 0 && $newBalance > $creditLimit) {
                        throw new \Exception("কাস্টমারের ক্রেডিট লিমিট অতিক্রম করেছে। বর্তমান বাকি: {$customer->balance}, ক্রেডিট লিমিট: {$creditLimit}, নতুন বাকি: {$sale->due}");
                    }

                    $customer->increment('balance', (float) $sale->due);
                    Log::info("Customer balance updated: {$newBalance}");
                }
            }

            // Handle payment if any
            if ($sale->paid > 0) {
                Log::info("Processing payment of {$sale->paid} to bank account ID: {$request->bank_account_id}");

                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                $bankAccount->transactions()->create([
                    'transaction_type' => 'in',
                    'amount' => $sale->paid,
                    'date' => $saleDate,
                    'description' => "Payment received for invoice {$sale->invoice_no}",
                    'created_by' => $createdBy,
                ]);

                // running_balance + current_balance: BankTransactionObserver
            }

            DB::commit();
            Log::info("Transaction committed successfully with invoice number: {$invoiceNumber}");

            if ($request->wantsJson() || $request->input('is_offline_sync')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale processed successfully',
                    'sale' => $sale,
                ]);
            }

            return to_route('admin.pos.index')->with('sale', $sale);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('POS Validation Error: '.json_encode($e->errors()));

            if ($request->wantsJson() || $request->input('is_offline_sync')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Error: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());

            if ($request->wantsJson() || $request->input('is_offline_sync')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function printReceipt($id)
    {
        // Fetch the sale with related data, including product categories
        $sale = Sale::with([
            'saleItems.product.category', // Include product category relationship
            'customer',
            'createdBy',
        ])->findOrFail($id);

        // Group items by category
        $itemsByCategory = $sale->saleItems->groupBy(function ($item) {
            return $item->product->category->name ?? 'Uncategorized';
        });

        // Calculate category subtotals
        $categoryTotals = [];
        foreach ($itemsByCategory as $category => $items) {
            $categoryTotals[$category] = $items->sum('subtotal');
        }

        $f = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        $amountInWords = ucfirst($f->format((float) $sale->total)).' taka only';

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'itemsByCategory' => $itemsByCategory, // Pass the grouped items
            'categoryTotals' => $categoryTotals,
            'amountInWords' => $amountInWords,
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address'),
                'phone' => config('app.phone'),
                'email' => config('app.email'),
            ],
        ]);

        return $pdf->stream("receipt-{$sale->invoice_no}.pdf");
    }

    protected function generateUniqueInvoiceNumber($date)
    {
        // Find highest sequence number for today including soft-deleted rows
        $lastSale = Sale::withTrashed()
            ->where('invoice_no', 'like', "INV-{$date}-%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastSale && preg_match('/INV-'.$date.'-(\d+)/', $lastSale->invoice_no, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        // Auto-skip any existing or soft-deleted invoice numbers
        while (Sale::withTrashed()->where('invoice_no', 'INV-'.$date.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT))->exists()) {
            $nextSequence++;
        }

        return 'INV-'.$date.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
