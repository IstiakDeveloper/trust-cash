<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleReturnPosting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::with(['customer', 'sale', 'bankAccount', 'creator']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('return_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
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
            'idempotency_key' => 'nullable|string|max:100',
            'sale_id' => 'required|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'return_date' => 'required|date',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_status' => 'required|string|in:completed,credited_to_due',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $saleReturn = app(SaleReturnPosting::class)->post($validated);

            return redirect()->route('admin.returns.show', $saleReturn->id)
                ->with('success', 'Return processed at the original sale cost.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
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
