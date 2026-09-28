<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\Fund;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReceiptPaymentController extends Controller
{
    protected function getCalculatedBalanceUpToDate(?int $bankAccountId, string $dateInclusive): float
    {
        $activeAccountIds = BankAccount::active()->pluck('id');

        if ($bankAccountId) {
            $bankAccount = BankAccount::findOrFail($bankAccountId);
            $openingBalance = (float) $bankAccount->opening_balance;

            $transactionsSum = DB::table('bank_transactions')
                ->where('bank_account_id', $bankAccountId)
                ->where('date', '<=', $dateInclusive)
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('description')
                        ->orWhere('description', 'NOT LIKE', '%Rounding Adjustment%');
                })
                ->sum(DB::raw('CASE
                    WHEN transaction_type = "in" THEN amount
                    ELSE -amount
                END'));

            return round($openingBalance + (float) $transactionsSum, 2);
        }

        // All Bank Accounts
        $totalOpeningBalance = (float) BankAccount::active()->sum('opening_balance');

        $transactionsSum = DB::table('bank_transactions')
            ->whereIn('bank_account_id', $activeAccountIds)
            ->where('date', '<=', $dateInclusive)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', 'NOT LIKE', '%Rounding Adjustment%');
            })
            ->sum(DB::raw('CASE
                WHEN transaction_type = "in" THEN amount
                ELSE -amount
            END'));

        return round($totalOpeningBalance + (float) $transactionsSum, 2);
    }

    public function index(Request $request)
    {
        // Support date-to-date range or fallback to month/year
        if ($request->filled(['start_date', 'end_date'])) {
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->end_date)->format('Y-m-d');
        } elseif ($request->filled(['year', 'month'])) {
            $date = Carbon::createFromDate($request->year, $request->month, 1);
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        }

        $bankAccounts = BankAccount::active()
            ->select('id', 'account_name', 'bank_name', 'opening_balance')
            ->get();

        // Default to all accounts (null) unless a specific account is requested
        $selectedBankAccountId = null;
        if ($request->filled('bank_account_id') && $request->bank_account_id !== 'all' && $request->bank_account_id !== '') {
            $found = $bankAccounts->firstWhere('id', (int) $request->input('bank_account_id'));
            if ($found) {
                $selectedBankAccountId = $found->id;
            }
        }

        $receiptData = $this->getReceiptData($startDate, $endDate, $selectedBankAccountId);
        $paymentData = $this->getPaymentData($startDate, $endDate, $selectedBankAccountId);

        return Inertia::render('Admin/ReceiptPayment/Index', [
            'bankAccounts' => $bankAccounts,
            'selectedBankAccountId' => $selectedBankAccountId,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'bank_account_id' => $selectedBankAccountId,
            ],
            'receipt' => $receiptData,
            'payment' => $paymentData,
        ]);
    }

    protected function emptyReceiptData(): array
    {
        return [
            'opening_cash_on_bank' => ['period' => 0.0, 'cumulative' => 0.0],
            'sale_collection' => ['period' => 0.0, 'cumulative' => 0.0],
            'extra_income' => ['total' => ['period' => 0.0, 'cumulative' => 0.0], 'categories' => []],
            'fund_receive' => ['total' => ['period' => 0.0, 'cumulative' => 0.0], 'items' => []],
            'total' => ['period' => 0.0, 'cumulative' => 0.0],
        ];
    }

    protected function emptyPaymentData(): array
    {
        return [
            'purchase' => ['period' => 0.0, 'cumulative' => 0.0],
            'supplier_payment' => ['period' => 0.0, 'cumulative' => 0.0],
            'fixed_assets' => ['total' => ['period' => 0.0, 'cumulative' => 0.0], 'items' => []],
            'fund_refund' => ['total' => ['period' => 0.0, 'cumulative' => 0.0], 'items' => []],
            'expenses' => ['total' => ['period' => 0.0, 'cumulative' => 0.0], 'categories' => []],
            'closing_cash_at_bank' => ['period' => 0.0, 'cumulative' => 0.0],
            'total' => ['period' => 0.0, 'cumulative' => 0.0],
        ];
    }

    protected function getReceiptData(string $startDate, string $endDate, ?int $bankAccountId): array
    {
        $activeAccountIds = BankAccount::active()->pluck('id');
        $openingDate = Carbon::parse($startDate)->subDay()->format('Y-m-d');

        // 1. Opening Cash (Period: balance at day before startDate; Cumulative: bank initial opening balance)
        $openingPeriod = $this->getCalculatedBalanceUpToDate($bankAccountId, $openingDate);
        $openingCumulative = $bankAccountId
            ? (float) BankAccount::findOrFail($bankAccountId)->opening_balance
            : (float) BankAccount::active()->sum('opening_balance');

        // 2. Sale Collection
        $salesPeriod = (float) BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', 'Payment received%')
                    ->orWhere('description', 'LIKE', '%invoice%')
                    ->orWhere('description', 'LIKE', '%customer%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $salesCumulative = (float) BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', 'Payment received%')
                    ->orWhere('description', 'LIKE', '%invoice%')
                    ->orWhere('description', 'LIKE', '%customer%');
            })
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // 3. Extra Income Categories
        $extraIncomeCategories = ExtraIncome::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select('category_id')
            ->distinct()
            ->with('category')
            ->get()
            ->map(function ($row) use ($bankAccountId, $activeAccountIds, $startDate, $endDate) {
                $catId = $row->category_id;
                $period = (float) ExtraIncome::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
                    ->where('category_id', $catId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->whereNull('deleted_at')
                    ->sum('amount');
                $cumulative = (float) ExtraIncome::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
                    ->where('category_id', $catId)
                    ->where('date', '<=', $endDate)
                    ->whereNull('deleted_at')
                    ->sum('amount');

                return [
                    'category' => $row->category ? $row->category->name : 'Uncategorized',
                    'period' => $period,
                    'cumulative' => $cumulative,
                ];
            })
            ->values()
            ->toArray();

        $extraIncomeTotalPeriod = (float) ExtraIncome::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $extraIncomeTotalCumulative = (float) ExtraIncome::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // 4. Fund Receive (Fund In)
        $fundInTransactions = BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fund in%')
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->get();

        $fundInMap = [];
        foreach ($fundInTransactions as $tx) {
            $name = str_replace('Fund in: ', 'Fund In - ', $tx->description);
            if (!isset($fundInMap[$name])) {
                $fundInMap[$name] = ['name' => $name, 'period' => 0.0, 'cumulative' => 0.0];
            }
            $txDate = Carbon::parse($tx->date)->format('Y-m-d');
            if ($txDate >= $startDate && $txDate <= $endDate) {
                $fundInMap[$name]['period'] += (float) $tx->amount;
            }
            $fundInMap[$name]['cumulative'] += (float) $tx->amount;
        }
        $fundInItems = array_values($fundInMap);

        $fundInTotalPeriod = (float) BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fund in%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $fundInTotalCumulative = (float) BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fund in%')
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        $totalReceiptPeriod = round($openingPeriod + $salesPeriod + $extraIncomeTotalPeriod + $fundInTotalPeriod, 2);
        $totalReceiptCumulative = round($openingCumulative + $salesCumulative + $extraIncomeTotalCumulative + $fundInTotalCumulative, 2);

        return [
            'opening_cash_on_bank' => [
                'period' => $openingPeriod,
                'cumulative' => $openingCumulative,
            ],
            'sale_collection' => [
                'period' => $salesPeriod,
                'cumulative' => $salesCumulative,
            ],
            'extra_income' => [
                'categories' => $extraIncomeCategories,
                'total' => [
                    'period' => $extraIncomeTotalPeriod,
                    'cumulative' => $extraIncomeTotalCumulative,
                ],
            ],
            'fund_receive' => [
                'items' => $fundInItems,
                'total' => [
                    'period' => $fundInTotalPeriod,
                    'cumulative' => $fundInTotalCumulative,
                ],
            ],
            'total' => [
                'period' => $totalReceiptPeriod,
                'cumulative' => $totalReceiptCumulative,
            ],
        ];
    }

    protected function getPaymentData(string $startDate, string $endDate, ?int $bankAccountId): array
    {
        $activeAccountIds = BankAccount::active()->pluck('id');

        // 1. Purchase Net
        $purchasePeriod = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Stock purchase%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $purchaseRefundPeriod = (float) BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', '%Refund%')
                    ->orWhere('description', 'LIKE', '%cancelled%')
                    ->orWhere('description', 'LIKE', '%deleted%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $netPurchasePeriod = $purchasePeriod - $purchaseRefundPeriod;

        $purchaseCumulative = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Stock purchase%')
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        $purchaseRefundCumulative = (float) BankTransaction::where('transaction_type', 'in')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', '%Refund%')
                    ->orWhere('description', 'LIKE', '%cancelled%')
                    ->orWhere('description', 'LIKE', '%deleted%');
            })
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        $netPurchaseCumulative = $purchaseCumulative - $purchaseRefundCumulative;

        // 2. Supplier Payment (Due paid)
        $supplierPaymentPeriod = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', 'Supplier Payment%')
                    ->orWhere('description', 'LIKE', 'Purchase Payment%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $supplierPaymentCumulative = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', 'Supplier Payment%')
                    ->orWhere('description', 'LIKE', 'Purchase Payment%');
            })
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // 3. Fixed Asset Purchases (detailed breakdown like expenses)
        $faItems = \App\Models\FixedAssetItem::with('asset')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('purchase_date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->get();

        $fixedAssetBreakdown = [];
        $linkedBankTxIds = [];

        foreach ($faItems as $item) {
            if ($item->bank_transaction_id) {
                $linkedBankTxIds[] = $item->bank_transaction_id;
            }
            $name = $item->asset ? ($item->asset->name . ' - ' . $item->item_name) : $item->item_name;
            if (!isset($fixedAssetBreakdown[$name])) {
                $fixedAssetBreakdown[$name] = ['name' => $name, 'period' => 0.0, 'cumulative' => 0.0];
            }
            $itemDate = Carbon::parse($item->purchase_date)->format('Y-m-d');
            if ($itemDate >= $startDate && $itemDate <= $endDate) {
                $fixedAssetBreakdown[$name]['period'] += (float) $item->purchase_price;
            }
            $fixedAssetBreakdown[$name]['cumulative'] += (float) $item->purchase_price;
        }

        // Include any legacy/direct Fixed Asset bank transactions
        $unlinkedFaTxs = BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where(function ($query) {
                $query->where('description', 'LIKE', 'Fixed Asset%')
                    ->orWhere('description', 'LIKE', 'Fixed Asset Purchase%');
            })
            ->whereNotIn('id', $linkedBankTxIds)
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->get();

        foreach ($unlinkedFaTxs as $tx) {
            $cleanName = preg_replace('/^Fixed Asset( Purchase)?:\s*/i', '', $tx->description);
            if (!isset($fixedAssetBreakdown[$cleanName])) {
                $fixedAssetBreakdown[$cleanName] = ['name' => $cleanName, 'period' => 0.0, 'cumulative' => 0.0];
            }
            $txDate = Carbon::parse($tx->date)->format('Y-m-d');
            if ($txDate >= $startDate && $txDate <= $endDate) {
                $fixedAssetBreakdown[$cleanName]['period'] += (float) $tx->amount;
            }
            $fixedAssetBreakdown[$cleanName]['cumulative'] += (float) $tx->amount;
        }

        $fixedAssetItems = array_values($fixedAssetBreakdown);

        $fixedAssetTotalPeriod = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fixed Asset%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $fixedAssetTotalCumulative = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fixed Asset%')
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // 4. Fund Refund / Fund Out
        $fundOutTransactions = BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fund out%')
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->get();

        $fundOutMap = [];
        foreach ($fundOutTransactions as $tx) {
            $name = str_replace('Fund out: ', 'Fund Out - ', $tx->description);
            if (!isset($fundOutMap[$name])) {
                $fundOutMap[$name] = ['name' => $name, 'period' => 0.0, 'cumulative' => 0.0];
            }
            $txDate = Carbon::parse($tx->date)->format('Y-m-d');
            if ($txDate >= $startDate && $txDate <= $endDate) {
                $fundOutMap[$name]['period'] += (float) $tx->amount;
            }
            $fundOutMap[$name]['cumulative'] += (float) $tx->amount;
        }
        $fundOutItems = array_values($fundOutMap);

        $fundRefundPeriod = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fund out%')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $fundRefundCumulative = (float) BankTransaction::where('transaction_type', 'out')
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('description', 'LIKE', 'Fund out%')
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // 5. Expenses
        $expenseCategories = Expense::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->select('expense_category_id')
            ->distinct()
            ->with('category')
            ->get()
            ->map(function ($row) use ($bankAccountId, $activeAccountIds, $startDate, $endDate) {
                $catId = $row->expense_category_id;
                $period = (float) Expense::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
                    ->where('expense_category_id', $catId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->whereNull('deleted_at')
                    ->sum('amount');
                $cumulative = (float) Expense::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
                    ->where('expense_category_id', $catId)
                    ->where('date', '<=', $endDate)
                    ->whereNull('deleted_at')
                    ->sum('amount');

                return [
                    'category' => $row->category ? $row->category->name : 'Uncategorized',
                    'period' => $period,
                    'cumulative' => $cumulative,
                ];
            })
            ->values()
            ->toArray();

        $expensesTotalPeriod = (float) Expense::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');

        $expensesTotalCumulative = (float) Expense::when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('date', '<=', $endDate)
            ->whereNull('deleted_at')
            ->sum('amount');

        // 6. Closing Cash at Bank (Closing balance as of $endDate)
        $closingCashPeriod = $this->getCalculatedBalanceUpToDate($bankAccountId, $endDate);
        $closingCashCumulative = $closingCashPeriod;

        // 7. Total Payment
        $totalPaymentPeriod = round($netPurchasePeriod + $supplierPaymentPeriod + $fixedAssetTotalPeriod + $fundRefundPeriod + $expensesTotalPeriod + $closingCashPeriod, 2);
        $totalPaymentCumulative = round($netPurchaseCumulative + $supplierPaymentCumulative + $fixedAssetTotalCumulative + $fundRefundCumulative + $expensesTotalCumulative + $closingCashCumulative, 2);

        return [
            'purchase' => [
                'period' => $netPurchasePeriod,
                'cumulative' => $netPurchaseCumulative,
            ],
            'supplier_payment' => [
                'period' => $supplierPaymentPeriod,
                'cumulative' => $supplierPaymentCumulative,
            ],
            'fixed_assets' => [
                'items' => $fixedAssetItems,
                'total' => [
                    'period' => $fixedAssetTotalPeriod,
                    'cumulative' => $fixedAssetTotalCumulative,
                ],
            ],
            'fund_refund' => [
                'items' => $fundOutItems,
                'total' => [
                    'period' => $fundRefundPeriod,
                    'cumulative' => $fundRefundCumulative,
                ],
            ],
            'expenses' => [
                'categories' => $expenseCategories,
                'total' => [
                    'period' => $expensesTotalPeriod,
                    'cumulative' => $expensesTotalCumulative,
                ],
            ],
            'closing_cash_at_bank' => [
                'period' => $closingCashPeriod,
                'cumulative' => $closingCashCumulative,
            ],
            'total' => [
                'period' => $totalPaymentPeriod,
                'cumulative' => $totalPaymentCumulative,
            ],
        ];
    }

    public function downloadPdf(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'year' => 'nullable|integer',
                'month' => 'nullable|integer|between:1,12',
                'bank_account_id' => 'nullable|string',
            ]);

            $bankAccounts = BankAccount::active()
                ->select('id', 'account_name', 'bank_name', 'opening_balance')
                ->get();

            $selectedAccount = null;
            if (!empty($validatedData['bank_account_id']) && $validatedData['bank_account_id'] !== 'all') {
                $selectedAccount = $bankAccounts->firstWhere('id', (int) $validatedData['bank_account_id']);
            }
            $selectedBankAccountId = $selectedAccount?->id;

            // Determine date range
            if (!empty($validatedData['start_date']) && !empty($validatedData['end_date'])) {
                $startDate = Carbon::parse($validatedData['start_date'])->format('Y-m-d');
                $endDate = Carbon::parse($validatedData['end_date'])->format('Y-m-d');
            } elseif (!empty($validatedData['year']) && !empty($validatedData['month'])) {
                $date = Carbon::create($validatedData['year'], $validatedData['month'], 1);
                $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
                $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
            } else {
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }

            // Get data
            $receiptData = $this->getReceiptData($startDate, $endDate, $selectedBankAccountId);
            $paymentData = $this->getPaymentData($startDate, $endDate, $selectedBankAccountId);

            $locale = $request->input('locale', 'bn');
            $isBn = ($locale === 'bn');

            $pdf = PDF::loadView('pdf.receipt-payment', [
                'receipt' => $receiptData,
                'payment' => $paymentData,
                'bank_account' => $selectedAccount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'isBn' => $isBn,
                'locale' => $locale,
            ]);

            $accLabel = $selectedAccount ? "account-{$selectedAccount->id}" : 'all-accounts';
            return $pdf->download("receipt-payment-{$startDate}-to-{$endDate}-{$accLabel}.pdf");

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed:', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('PDF Download Failed:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to generate PDF', 'details' => $e->getMessage()], 500);
        }
    }
}
