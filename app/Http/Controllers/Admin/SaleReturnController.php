<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::with(['customer', 'sale', 'bankAccount', 'creator']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('return_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $returns = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_returns_amount' => SaleReturn::sum('total_amount'),
            'total_refunded' => SaleReturn::sum('refund_amount'),
            'total_count' => SaleReturn::count(),
        ];

        return Inertia::render('Admin/Returns/Index', [
            'returns' => $returns,
            'filters' => $request->only(['search']),
            'stats' => $stats,
        ]);
    }

    public function create(Request $request)
    {
        $sale = null;
        if ($request->filled('sale_id')) {
            $sale = Sale::with(['customer', 'saleItems.product', 'saleItems.productVariant'])->find($request->sale_id);
        }

        $customers = Customer::where('status', true)->select('id', 'name', 'phone', 'balance')->get();
        $bankAccounts = BankAccount::active()->select('id', 'bank_name', 'current_balance')->get();

        return Inertia::render('Admin/Returns/Create', [
            'initialSale' => $sale,
            'customers' => $customers,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function searchSale(Request $request)
    {
        $query = $request->query('q');
        $sales = Sale::where('invoice_no', 'like', "%{$query}%")
            ->with(['customer', 'saleItems.product', 'saleItems.productVariant'])
            ->take(10)
            ->get();

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'return_date' => 'required|date',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_status' => 'required|string|in:completed,credited_to_due',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $today = date('Ymd');
            $latest = SaleReturn::whereDate('created_at', today())->latest()->first();
            $nextSeq = $latest ? ((int) substr($latest->return_number, -4)) + 1 : 1;
            $returnNumber = 'RET-' . $today . '-' . sprintf('%04d', $nextSeq);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_price']);
            }

            $refundAmount = $validated['refund_amount'] ?? $totalAmount;

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'sale_id' => $validated['sale_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'return_date' => $validated['return_date'],
                'total_amount' => $totalAmount,
                'refund_amount' => $refundAmount,
                'refund_status' => $validated['refund_status'],
                'reason' => $validated['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Save items & restore stock
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // Increase stock
                $stock = ProductStock::create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'total_quantity' => $item['quantity'],
                    'available_quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_price'],
                    'total_cost' => $subtotal,
                    'type' => 'sale_return',
                    'date' => $validated['return_date'],
                    'note' => "Sale Return #{$returnNumber}",
                    'created_by' => auth()->id(),
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'reference_type' => SaleReturn::class,
                    'reference_id' => $saleReturn->id,
                    'quantity' => $item['quantity'],
                    'before_quantity' => 0,
                    'after_quantity' => $item['quantity'],
                    'type' => 'return',
                    'created_by' => auth()->id(),
                ]);
            }

            // If cash/bank refund given
            if ($validated['refund_status'] === 'completed' && $refundAmount > 0 && !empty($validated['bank_account_id'])) {
                $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);

                // Create bank transaction (Observer automatically deducts from bank account balance)
                BankTransaction::create([
                    'bank_account_id' => $bankAccount->id,
                    'transaction_type' => 'out',
                    'amount' => $refundAmount,
                    'description' => "Customer Sale Refund: #{$returnNumber}",
                    'date' => $validated['return_date'],
                    'created_by' => auth()->id(),
                ]);
            }

            // If credited to customer balance
            if ($validated['refund_status'] === 'credited_to_due' && !empty($validated['customer_id'])) {
                $customer = Customer::findOrFail($validated['customer_id']);
                $customer->decrement('balance', $refundAmount);
            }

            DB::commit();

            return redirect()->route('admin.returns.show', $saleReturn->id)
                ->with('success', "Return #{$returnNumber} processed and inventory restocked.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Return failed: ' . $e->getMessage());
        }
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['customer', 'sale', 'bankAccount', 'creator', 'items.product', 'items.variant']);

        return Inertia::render('Admin/Returns/Show', [
            'saleReturn' => $saleReturn,
        ]);
    }
}
