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
use App\Models\SupplierPayment;
use App\Models\Unit;
use App\Services\PurchaseReversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'creator', 'bankAccount']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        $purchases = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_purchases_amount' => Purchase::sum('total_amount'),
            'total_paid' => Purchase::sum('paid_amount'),
            'total_due' => Purchase::sum('due_amount'),
            'total_count' => Purchase::count(),
        ];

        return Inertia::render('Admin/Purchases/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search', 'payment_status', 'from_date', 'to_date']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')
            ->select('id', 'name', 'company_name', 'phone', 'current_balance')
            ->get();

        $products = Product::where('status', true)
            ->with(['variants', 'unit'])
            ->select('id', 'name', 'sku', 'barcode', 'cost_price', 'selling_price', 'unit_id')
            ->get();

        $bankAccounts = BankAccount::active()
            ->select('id', 'bank_name', 'account_number', 'current_balance')
            ->get();

        $units = Unit::all();

        return Inertia::render('Admin/Purchases/Create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'bankAccounts' => $bankAccounts,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'discount_type' => 'nullable|string|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if (($validated['paid_amount'] ?? 0) > 0 && empty($validated['bank_account_id'])) {
            return back()->withErrors([
                'bank_account_id' => 'Select a bank or cash account for the immediate payment.',
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Generate unique purchase number
            $today = date('Ymd');
            $latest = Purchase::whereDate('created_at', today())->latest()->first();
            $nextSeq = $latest ? ((int) substr($latest->purchase_number, -4)) + 1 : 1;
            $purchaseNumber = 'PUR-' . $today . '-' . sprintf('%04d', $nextSeq);

            // Compute subtotal
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['purchase_price'] * $item['quantity']);
            }

            $discountAmount = $validated['discount_amount'] ?? 0;
            if (($validated['discount_type'] ?? 'fixed') === 'percentage') {
                $discountAmount = ($subtotal * $discountAmount) / 100;
            }

            $taxAmount = $validated['tax_amount'] ?? 0;
            $shippingCost = $validated['shipping_cost'] ?? 0;
            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount + $shippingCost);
            $paidAmount = min($totalAmount, $validated['paid_amount'] ?? 0);
            $dueAmount = max(0, $totalAmount - $paidAmount);
            $paymentBankAccountId = $paidAmount > 0 ? ($validated['bank_account_id'] ?? null) : null;

            $paymentStatus = 'due';
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            // Create purchase record
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $validated['supplier_id'],
                'bank_account_id' => $paymentBankAccountId,
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'purchase_status' => 'received',
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Save items & increase stock
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['purchase_price'] * $item['quantity'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
                    'purchase_price' => $item['purchase_price'],
                    'selling_price' => $item['selling_price'] ?? null,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ]);

                // Create product stock record
                $stock = ProductStock::create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'total_quantity' => $item['quantity'],
                    'available_quantity' => $item['quantity'],
                    'unit_cost' => $item['purchase_price'],
                    'total_cost' => $itemSubtotal,
                    'type' => 'purchase',
                    'date' => $validated['purchase_date'],
                    'bank_account_id' => $paymentBankAccountId,
                    'note' => "Purchase #{$purchaseNumber}",
                    'created_by' => auth()->id(),
                ]);

                // Record stock movement
                $prevStock = ProductStock::where('product_id', $item['product_id'])
                    ->where('id', '!=', $stock->id)
                    ->sum('available_quantity');

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'quantity' => $item['quantity'],
                    'before_quantity' => $prevStock,
                    'after_quantity' => $prevStock + $item['quantity'],
                    'type' => 'purchase',
                    'created_by' => auth()->id(),
                ]);

                // Update product cost_price if provided
                if (!empty($item['selling_price']) || !empty($item['purchase_price'])) {
                    $prod = Product::find($item['product_id']);
                    if ($prod) {
                        $prodUpdates = [];
                        if ($item['purchase_price'] > 0) $prodUpdates['cost_price'] = $item['purchase_price'];
                        if (!empty($item['selling_price']) && $item['selling_price'] > 0) $prodUpdates['selling_price'] = $item['selling_price'];
                        if (!empty($prodUpdates)) $prod->update($prodUpdates);
                    }
                }
            }

            // Handle payment if paid_amount > 0
            if ($paidAmount > 0 && $paymentBankAccountId) {
                $bankAccount = BankAccount::findOrFail($paymentBankAccountId);

                SupplierPayment::create([
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_id' => $purchase->id,
                    'bank_account_id' => $bankAccount->id,
                    'amount' => $paidAmount,
                    'payment_method' => 'cash',
                    'payment_date' => $validated['purchase_date'],
                    'reference_no' => $purchaseNumber,
                    'note' => "Payment for Purchase #{$purchaseNumber}",
                    'created_by' => auth()->id(),
                ]);

                // Create bank transaction (Observer automatically deducts from bank account balance)
                BankTransaction::create([
                    'bank_account_id' => $bankAccount->id,
                    'transaction_type' => 'out',
                    'amount' => $paidAmount,
                    'description' => "Purchase Payment: #{$purchaseNumber}",
                    'date' => $validated['purchase_date'],
                    'created_by' => auth()->id(),
                ]);
            }

            // Update supplier balance (increase by due_amount)
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $supplier->increment('current_balance', $dueAmount);

            DB::commit();

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', "Purchase #{$purchaseNumber} created and stock updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Purchase failed: ' . $e->getMessage());
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'bankAccount',
            'creator',
            'items.product',
            'items.variant',
            'items.unit',
            'payments.bankAccount'
        ]);

        return Inertia::render('Admin/Purchases/Show', [
            'purchase' => $purchase,
        ]);
    }

    public function destroy(Purchase $purchase, PurchaseReversalService $reversalService)
    {
        try {
            $result = $reversalService->reversePurchase($purchase);

            return redirect()->route('admin.purchases.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
