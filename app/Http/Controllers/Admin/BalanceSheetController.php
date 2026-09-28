<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExtraIncome;
use App\Models\FixedAsset;
use App\Models\FixedAssetItem;
use App\Models\Fund;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        // Validate and set date range
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Set default to current month if no dates provided
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        // Fund & Liabilities Section
        $fundAndLiabilities = $this->calculateFundAndLiabilities($startDate, $endDate);

        // Property & Assets Section
        $propertyAndAssets = $this->calculatePropertyAndAssets($startDate, $endDate);

        // Prepare data for Inertia render
        $data = [
            'fund_and_liabilities' => $fundAndLiabilities,
            'property_and_assets' => $propertyAndAssets,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ];

        return Inertia::render('Admin/Reports/BalanceSheet', $data);
    }

    private function calculateFundAndLiabilities(Carbon $startDate, Carbon $endDate)
    {
        // 1. Fund Calculation up to the end date
        $fundAmount = Fund::where('date', '<=', $endDate)
            ->whereIn('type', ['in', 'out'])
            ->get()
            ->reduce(function ($carry, $fund) {
                return $carry + ($fund->type === 'in' ? $fund->amount : -$fund->amount);
            }, 0);

        // Sales up to the end date
        $salesAmount = Sale::where('created_at', '<=', $endDate)
            ->sum('total');

        // Extra Income up to the end date
        $extraIncomeAmount = ExtraIncome::where('date', '<=', $endDate)
            ->sum('amount');

        // Expenses up to the end date
        $expensesAmount = Expense::where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // Cost of Goods Sold up to the end date
        $costOfGoodsSold = $this->calculateCostOfGoodsSold(null, $endDate);

        // Use the actual first transaction date for cumulative calculations
        $cumulativeStartDate = Carbon::parse('2024-12-29')->startOfDay();
        $productTotalProfit = Product::getProductAnalysis($cumulativeStartDate, $endDate)['totals']['total_profit'];

        // Supplier Due (Accounts Payable) up to the end date
        $supplierDue = $this->calculateSupplierDue($endDate);

        // Net Profit calculation
        $netProfit = $productTotalProfit + $extraIncomeAmount - $expensesAmount;
        $total = $fundAmount + $netProfit + $supplierDue;

        return [
            'fund' => [
                'period' => (float) $fundAmount,
            ],
            'net_profit' => [
                'period' => (float) $netProfit,
            ],
            'supplier_due' => [
                'period' => (float) $supplierDue,
            ],
            'total' => (float) $total,
        ];
    }

    private function calculateSupplierDue(Carbon $endDate)
    {
        // If end date is current (today or in the future), use the live current_balance of suppliers
        $isCurrentPeriod = $endDate->greaterThanOrEqualTo(Carbon::today()->startOfDay());

        if ($isCurrentPeriod) {
            return (float) Supplier::where('created_at', '<=', $endDate)
                ->where('current_balance', '>', 0)
                ->sum('current_balance');
        }

        // For past date filters, roll back from current_balance using subsequent purchases and payments
        $suppliers = Supplier::where('created_at', '<=', $endDate)->get();
        $totalDue = 0;

        foreach ($suppliers as $supplier) {
            $purchasesAfter = (float) DB::table('purchases')
                ->where('supplier_id', $supplier->id)
                ->where('purchase_date', '>', $endDate)
                ->whereNull('deleted_at')
                ->sum('due_amount');

            $paymentsAfter = (float) DB::table('supplier_payments')
                ->where('supplier_id', $supplier->id)
                ->where('payment_date', '>', $endDate)
                ->sum('amount');

            $historicalBalance = (float) $supplier->current_balance - $purchasesAfter + $paymentsAfter;
            $totalDue += max(0, $historicalBalance);
        }

        return $totalDue;
    }

    private function calculatePropertyAndAssets(Carbon $startDate, Carbon $endDate)
    {
        // 1. Bank Balance - Calculate total balance for all accounts at end date
        $bankBalance = $this->calculateBankBalance($endDate);

        // 2. Customer Due (Accounts Receivable) up to end date
        $customerDue = (float) (Customer::where('created_at', '<=', $endDate)->sum('balance') ?? 0);

        // 3. Fixed Assets up to the end date (from active items purchased on or before end date)
        $fixedAssets = (float) FixedAssetItem::where('purchase_date', '<=', $endDate)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->sum('current_value');

        // Optional breakdown by Asset head
        $assetsBreakdown = FixedAsset::with(['items' => function ($q) use ($endDate) {
            $q->where('purchase_date', '<=', $endDate)
              ->where('status', 'active')
              ->whereNull('deleted_at');
        }])->get()->map(function ($asset) {
            return [
                'name' => $asset->name,
                'code' => $asset->asset_code,
                'amount' => (float) $asset->items->sum('current_value'),
                'items_count' => $asset->items->count(),
            ];
        })->filter(fn ($item) => $item['amount'] > 0)->values()->toArray();
        // 4. Stock Value up to the end date
        // Use the actual first transaction date for accurate calculation
        $cumulativeStartDate = Carbon::parse('2024-12-29')->startOfDay();
        $analysis = Product::getProductAnalysis($cumulativeStartDate, $endDate);
        $stockValue = $analysis['totals']['available_stock_value'];

        // Calculate Total
        $total = $bankBalance + $customerDue + $fixedAssets + $stockValue;

        return [
            'bank_balance' => [
                'period' => (float) $bankBalance,
            ],
            'customer_due' => [
                'period' => (float) $customerDue,
            ],
            'fixed_assets' => (float) $fixedAssets,
            'fixed_assets_breakdown' => $assetsBreakdown,
            'stock_value' => [
                'period' => (float) $stockValue,
            ],
            'total' => (float) $total,
        ];
    }

    private function calculateCostOfGoodsSold(?Carbon $startDate, Carbon $endDate)
    {
        $query = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.created_at', '<=', $endDate);

        // If start date is provided, add the start date condition
        if ($startDate) {
            $query->where('s.created_at', '>=', $startDate);
        }

        $soldProducts = $query->select(
            'si.product_id',
            'si.quantity',
            's.created_at'
        )->get();

        $soldProductsCost = 0;
        foreach ($soldProducts as $sale) {
            $costAtSaleTime = DB::table('product_stocks as ps')
                ->where('product_id', $sale->product_id)
                ->where('created_at', '<=', $sale->created_at)
                ->where('type', 'purchase')
                ->select(DB::raw('
                    CASE
                        WHEN SUM(quantity) > 0
                        THEN CAST(SUM(total_cost) / SUM(quantity) AS DECIMAL(15,6))
                        ELSE 0
                    END as unit_cost
                '))
                ->first()
                ->unit_cost ?? 0;

            $soldProductsCost += $sale->quantity * $costAtSaleTime;
        }

        return $soldProductsCost;
    }

    private function calculateBankBalance(Carbon $endDate)
    {
        // Get all bank accounts
        $accounts = BankAccount::all();

        $totalBalance = 0;

        foreach ($accounts as $account) {
            // Get opening balance
            $balance = $account->opening_balance;

            // Add all transactions up to end date
            $transactions = DB::table('bank_transactions')
                ->where('bank_account_id', $account->id)
                ->where('date', '<=', $endDate)
                ->whereNull('deleted_at')
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($transactions as $transaction) {
                if ($transaction->transaction_type === 'in') {
                    $balance += $transaction->amount;
                } else {
                    $balance -= $transaction->amount;
                }
            }

            $totalBalance += $balance;
        }

        return $totalBalance;
    }

    public function downloadPdf(Request $request)
    {
        // Validate and set date range (same as index method)
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Set default to current month if no dates provided
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth()->endOfDay();

        // Fund & Liabilities Section
        $fundAndLiabilities = $this->calculateFundAndLiabilities($startDate, $endDate);

        // Property & Assets Section
        $propertyAndAssets = $this->calculatePropertyAndAssets($startDate, $endDate);

        $locale = $request->input('locale', 'bn');
        $isBn = ($locale === 'bn');

        // Prepare data for PDF
        $data = [
            'fund_and_liabilities' => $fundAndLiabilities,
            'property_and_assets' => $propertyAndAssets,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'isBn' => $isBn,
            'locale' => $locale,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.balance-sheet', $data);

        // Generate filename
        $filename = 'balance-sheet-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        // Download PDF
        return $pdf->download($filename);
    }
}
