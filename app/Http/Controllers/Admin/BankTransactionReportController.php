<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BankTransactionReportController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = (int) $request->input('month', Carbon::now()->month);
        $selectedYear = (int) $request->input('year', Carbon::now()->year);

        $bankAccounts = BankAccount::active()
            ->select('id', 'account_name', 'bank_name', 'opening_balance', 'current_balance')
            ->get();

        // Default to all accounts (null) unless a specific account is requested
        $selectedBankAccount = null;
        if ($request->filled('bank_account_id') && $request->bank_account_id !== 'all' && $request->bank_account_id !== '') {
            $found = $bankAccounts->firstWhere('id', (int) $request->bank_account_id);
            if ($found) {
                $selectedBankAccount = $found->id;
            }
        }

        $selectedAccount = $selectedBankAccount ? $bankAccounts->firstWhere('id', $selectedBankAccount) : null;
        $activeAccountIds = $bankAccounts->pluck('id');

        // Previous month closing balance
        $previousMonthEnd = Carbon::create($selectedYear, $selectedMonth, 1)->subDay()->format('Y-m-d');
        $initialOpening = $selectedBankAccount
            ? (float) $selectedAccount->opening_balance
            : (float) $bankAccounts->sum('opening_balance');

        $prevTxsSum = DB::table('bank_transactions')
            ->when($selectedBankAccount, fn($q) => $q->where('bank_account_id', $selectedBankAccount), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('date', '<=', $previousMonthEnd)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', 'NOT LIKE', '%Rounding Adjustment%');
            })
            ->sum(DB::raw('CASE
                WHEN transaction_type = "in" THEN amount
                ELSE -amount
            END'));

        $previousMonthBalance = round($initialOpening + (float) $prevTxsSum, 2);

        // Date range of selected month
        $startOfMonth = Carbon::create($selectedYear, $selectedMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Daily transactions aggregating all transaction types
        $dailyTransactions = DB::table('bank_transactions')
            ->select('date')
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND description LIKE "Fund in%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fund_in,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND (description LIKE "Payment received%" OR description LIKE "%invoice%" OR description LIKE "%customer%" OR description LIKE "Sale%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as payment_receive,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND (description LIKE "Extra Income%" OR description LIKE "Other Income%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as extra_income,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND deleted_at IS NULL AND (
                        description LIKE "%Refund%" OR
                        description LIKE "%cancelled%" OR
                        description LIKE "%deleted%"
                    )
                    THEN amount ELSE 0 END), 0) as refund,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND deleted_at IS NULL
                        AND description NOT LIKE "Fund in%"
                        AND description NOT LIKE "Payment received%"
                        AND description NOT LIKE "%invoice%"
                        AND description NOT LIKE "%customer%"
                        AND description NOT LIKE "Sale%"
                        AND description NOT LIKE "Extra Income%"
                        AND description NOT LIKE "Other Income%"
                        AND description NOT LIKE "%Refund%"
                        AND description NOT LIKE "%cancelled%"
                        AND description NOT LIKE "%deleted%"
                    THEN amount ELSE 0 END), 0) as other_in,

                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Fund out%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fund_out,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Stock purchase%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as purchase,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND (description LIKE "Supplier Payment%" OR description LIKE "Purchase Payment%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as supplier_payment,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND (description LIKE "Fixed Asset%" OR description LIKE "Fixed Asset Purchase%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fixed_asset,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Expense%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND deleted_at IS NULL
                        AND description NOT LIKE "Fund out%"
                        AND description NOT LIKE "Stock purchase%"
                        AND description NOT LIKE "Supplier Payment%"
                        AND description NOT LIKE "Purchase Payment%"
                        AND description NOT LIKE "Fixed Asset%"
                        AND description NOT LIKE "Expense%"
                    THEN amount ELSE 0 END), 0) as other_out')
            ->when($selectedBankAccount, fn($q) => $q->where('bank_account_id', $selectedBankAccount), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        // Extra incomes from extra_incomes table (if any unlinked to bank_transactions)
        $extraIncomes = DB::table('extra_incomes')
            ->select('date', DB::raw('SUM(amount) as total_amount'))
            ->when($selectedBankAccount, fn($q) => $q->where('bank_account_id', $selectedBankAccount), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->whereNull('deleted_at')
            ->groupBy('date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $allDates = [];
        $currentDate = $startOfMonth->copy();
        $runningBalance = $previousMonthBalance;

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayTx = $dailyTransactions->get($dateStr);
            $extraIncome = $extraIncomes->get($dateStr);

            $fundIn = $dayTx ? (float) $dayTx->fund_in : 0.0;
            $paymentReceive = $dayTx ? (float) $dayTx->payment_receive : 0.0;
            $refund = $dayTx ? (float) $dayTx->refund : 0.0;
            // Prefer transaction extra income, fallback/supplement with extra_incomes table
            $extraIncomeAmount = max(
                $dayTx ? (float) $dayTx->extra_income : 0.0,
                $extraIncome ? (float) $extraIncome->total_amount : 0.0
            );
            $otherIn = $dayTx ? (float) $dayTx->other_in : 0.0;

            $fundOut = $dayTx ? (float) $dayTx->fund_out : 0.0;
            $purchase = $dayTx ? (float) $dayTx->purchase : 0.0;
            $supplierPayment = $dayTx ? (float) $dayTx->supplier_payment : 0.0;
            $fixedAsset = $dayTx ? (float) $dayTx->fixed_asset : 0.0;
            $expense = $dayTx ? (float) $dayTx->expense : 0.0;
            $otherOut = $dayTx ? (float) $dayTx->other_out : 0.0;

            $totalIn = $fundIn + $paymentReceive + $extraIncomeAmount + $refund + $otherIn;
            $totalOut = $fundOut + $purchase + $supplierPayment + $fixedAsset + $expense + $otherOut;

            $runningBalance = $runningBalance + $totalIn - $totalOut;

            $allDates[] = [
                'date' => $dateStr,
                'in' => [
                    'fund' => round($fundIn, 2),
                    'payment' => round($paymentReceive, 2),
                    'extra' => round($extraIncomeAmount, 2),
                    'refund' => round($refund, 2),
                    'other' => round($otherIn, 2),
                    'total' => round($totalIn, 2),
                ],
                'out' => [
                    'fund' => round($fundOut, 2),
                    'purchase' => round($purchase, 2),
                    'supplier_payment' => round($supplierPayment, 2),
                    'fixed_asset' => round($fixedAsset, 2),
                    'expense' => round($expense, 2),
                    'other' => round($otherOut, 2),
                    'total' => round($totalOut, 2),
                ],
                'balance' => round($runningBalance, 2),
            ];

            $currentDate->addDay();
        }

        // Calculate month totals across all columns
        $totalInFund = (float) $dailyTransactions->sum('fund_in');
        $totalInPayment = (float) $dailyTransactions->sum('payment_receive');
        $totalInExtra = max((float) $dailyTransactions->sum('extra_income'), (float) $extraIncomes->sum('total_amount'));
        $totalInRefund = (float) $dailyTransactions->sum('refund');
        $totalInOther = (float) $dailyTransactions->sum('other_in');
        $totalMonthIn = $totalInFund + $totalInPayment + $totalInExtra + $totalInRefund + $totalInOther;

        $totalOutFund = (float) $dailyTransactions->sum('fund_out');
        $totalOutPurchase = (float) $dailyTransactions->sum('purchase');
        $totalOutSupplier = (float) $dailyTransactions->sum('supplier_payment');
        $totalOutFixedAsset = (float) $dailyTransactions->sum('fixed_asset');
        $totalOutExpense = (float) $dailyTransactions->sum('expense');
        $totalOutOther = (float) $dailyTransactions->sum('other_out');
        $totalMonthOut = $totalOutFund + $totalOutPurchase + $totalOutSupplier + $totalOutFixedAsset + $totalOutExpense + $totalOutOther;

        $monthTotals = [
            'in' => [
                'fund' => round($totalInFund, 2),
                'payment' => round($totalInPayment, 2),
                'extra' => round($totalInExtra, 2),
                'refund' => round($totalInRefund, 2),
                'other' => round($totalInOther, 2),
                'total' => round($totalMonthIn, 2),
            ],
            'out' => [
                'fund' => round($totalOutFund, 2),
                'purchase' => round($totalOutPurchase, 2),
                'supplier_payment' => round($totalOutSupplier, 2),
                'fixed_asset' => round($totalOutFixedAsset, 2),
                'expense' => round($totalOutExpense, 2),
                'other' => round($totalOutOther, 2),
                'total' => round($totalMonthOut, 2),
            ],
        ];

        // Total current balance of the selected account or all accounts
        $currentAccountBalance = $selectedBankAccount
            ? (float) $selectedAccount->current_balance
            : (float) $bankAccounts->sum('current_balance');

        return Inertia::render('Admin/Reports/BankTransactionReport', [
            'bankAccounts' => $bankAccounts,
            'selectedAccount' => $selectedAccount,
            'selectedBankAccount' => $selectedBankAccount,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'previousMonthBalance' => round($previousMonthBalance, 2),
            'dailyTransactions' => $allDates,
            'monthTotals' => $monthTotals,
            'currentAccountBalance' => round($currentAccountBalance, 2),
            'filters' => [
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'bank_account_id' => $selectedBankAccount,
            ],
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $selectedMonth = (int) $request->input('month', Carbon::now()->month);
        $selectedYear = (int) $request->input('year', Carbon::now()->year);

        $bankAccounts = BankAccount::active()
            ->select('id', 'account_name', 'bank_name', 'opening_balance', 'current_balance')
            ->get();

        $selectedBankAccount = null;
        if ($request->filled('bank_account_id') && $request->bank_account_id !== 'all' && $request->bank_account_id !== '') {
            $found = $bankAccounts->firstWhere('id', (int) $request->bank_account_id);
            if ($found) {
                $selectedBankAccount = $found->id;
            }
        }

        $selectedAccount = $selectedBankAccount ? $bankAccounts->firstWhere('id', $selectedBankAccount) : null;
        $activeAccountIds = $bankAccounts->pluck('id');

        $previousMonthEnd = Carbon::create($selectedYear, $selectedMonth, 1)->subDay()->format('Y-m-d');
        $initialOpening = $selectedBankAccount
            ? (float) $selectedAccount->opening_balance
            : (float) $bankAccounts->sum('opening_balance');

        $prevTxsSum = DB::table('bank_transactions')
            ->when($selectedBankAccount, fn($q) => $q->where('bank_account_id', $selectedBankAccount), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->where('date', '<=', $previousMonthEnd)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', 'NOT LIKE', '%Rounding Adjustment%');
            })
            ->sum(DB::raw('CASE
                WHEN transaction_type = "in" THEN amount
                ELSE -amount
            END'));

        $previousMonthBalance = round($initialOpening + (float) $prevTxsSum, 2);

        $startOfMonth = Carbon::create($selectedYear, $selectedMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $dailyTransactions = DB::table('bank_transactions')
            ->select('date')
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND description LIKE "Fund in%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fund_in,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND (description LIKE "Payment received%" OR description LIKE "%invoice%" OR description LIKE "%customer%" OR description LIKE "Sale%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as payment_receive,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND (description LIKE "Extra Income%" OR description LIKE "Other Income%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as extra_income,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND deleted_at IS NULL AND (
                        description LIKE "%Refund%" OR
                        description LIKE "%cancelled%" OR
                        description LIKE "%deleted%"
                    )
                    THEN amount ELSE 0 END), 0) as refund,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "in" AND deleted_at IS NULL
                        AND description NOT LIKE "Fund in%"
                        AND description NOT LIKE "Payment received%"
                        AND description NOT LIKE "%invoice%"
                        AND description NOT LIKE "%customer%"
                        AND description NOT LIKE "Sale%"
                        AND description NOT LIKE "Extra Income%"
                        AND description NOT LIKE "Other Income%"
                        AND description NOT LIKE "%Refund%"
                        AND description NOT LIKE "%cancelled%"
                        AND description NOT LIKE "%deleted%"
                    THEN amount ELSE 0 END), 0) as other_in,

                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Fund out%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fund_out,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Stock purchase%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as purchase,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND (description LIKE "Supplier Payment%" OR description LIKE "Purchase Payment%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as supplier_payment,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND (description LIKE "Fixed Asset%" OR description LIKE "Fixed Asset Purchase%") AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as fixed_asset,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND description LIKE "Expense%" AND deleted_at IS NULL
                    THEN amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE
                    WHEN transaction_type = "out" AND deleted_at IS NULL
                        AND description NOT LIKE "Fund out%"
                        AND description NOT LIKE "Stock purchase%"
                        AND description NOT LIKE "Supplier Payment%"
                        AND description NOT LIKE "Purchase Payment%"
                        AND description NOT LIKE "Fixed Asset%"
                        AND description NOT LIKE "Expense%"
                    THEN amount ELSE 0 END), 0) as other_out')
            ->when($selectedBankAccount, fn($q) => $q->where('bank_account_id', $selectedBankAccount), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $extraIncomes = DB::table('extra_incomes')
            ->select('date', DB::raw('SUM(amount) as total_amount'))
            ->when($selectedBankAccount, fn($q) => $q->where('bank_account_id', $selectedBankAccount), fn($q) => $q->whereIn('bank_account_id', $activeAccountIds))
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->whereNull('deleted_at')
            ->groupBy('date')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $allDates = [];
        $currentDate = $startOfMonth->copy();
        $runningBalance = $previousMonthBalance;

        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayTx = $dailyTransactions->get($dateStr);
            $extraIncome = $extraIncomes->get($dateStr);

            $fundIn = $dayTx ? (float) $dayTx->fund_in : 0.0;
            $paymentReceive = $dayTx ? (float) $dayTx->payment_receive : 0.0;
            $refund = $dayTx ? (float) $dayTx->refund : 0.0;
            $extraIncomeAmount = max(
                $dayTx ? (float) $dayTx->extra_income : 0.0,
                $extraIncome ? (float) $extraIncome->total_amount : 0.0
            );
            $otherIn = $dayTx ? (float) $dayTx->other_in : 0.0;

            $fundOut = $dayTx ? (float) $dayTx->fund_out : 0.0;
            $purchase = $dayTx ? (float) $dayTx->purchase : 0.0;
            $supplierPayment = $dayTx ? (float) $dayTx->supplier_payment : 0.0;
            $fixedAsset = $dayTx ? (float) $dayTx->fixed_asset : 0.0;
            $expense = $dayTx ? (float) $dayTx->expense : 0.0;
            $otherOut = $dayTx ? (float) $dayTx->other_out : 0.0;

            $totalIn = $fundIn + $paymentReceive + $extraIncomeAmount + $refund + $otherIn;
            $totalOut = $fundOut + $purchase + $supplierPayment + $fixedAsset + $expense + $otherOut;

            $runningBalance = $runningBalance + $totalIn - $totalOut;

            $allDates[] = [
                'date' => $dateStr,
                'in' => [
                    'fund' => round($fundIn, 2),
                    'payment' => round($paymentReceive, 2),
                    'extra' => round($extraIncomeAmount, 2),
                    'refund' => round($refund, 2),
                    'other' => round($otherIn, 2),
                    'total' => round($totalIn, 2),
                ],
                'out' => [
                    'fund' => round($fundOut, 2),
                    'purchase' => round($purchase, 2),
                    'supplier_payment' => round($supplierPayment, 2),
                    'fixed_asset' => round($fixedAsset, 2),
                    'expense' => round($expense, 2),
                    'other' => round($otherOut, 2),
                    'total' => round($totalOut, 2),
                ],
                'balance' => round($runningBalance, 2),
            ];

            $currentDate->addDay();
        }

        $totalInFund = (float) $dailyTransactions->sum('fund_in');
        $totalInPayment = (float) $dailyTransactions->sum('payment_receive');
        $totalInExtra = max((float) $dailyTransactions->sum('extra_income'), (float) $extraIncomes->sum('total_amount'));
        $totalInRefund = (float) $dailyTransactions->sum('refund');
        $totalInOther = (float) $dailyTransactions->sum('other_in');
        $totalMonthIn = $totalInFund + $totalInPayment + $totalInExtra + $totalInRefund + $totalInOther;

        $totalOutFund = (float) $dailyTransactions->sum('fund_out');
        $totalOutPurchase = (float) $dailyTransactions->sum('purchase');
        $totalOutSupplier = (float) $dailyTransactions->sum('supplier_payment');
        $totalOutFixedAsset = (float) $dailyTransactions->sum('fixed_asset');
        $totalOutExpense = (float) $dailyTransactions->sum('expense');
        $totalOutOther = (float) $dailyTransactions->sum('other_out');
        $totalMonthOut = $totalOutFund + $totalOutPurchase + $totalOutSupplier + $totalOutFixedAsset + $totalOutExpense + $totalOutOther;

        $monthTotals = [
            'in' => [
                'fund' => round($totalInFund, 2),
                'payment' => round($totalInPayment, 2),
                'extra' => round($totalInExtra, 2),
                'refund' => round($totalInRefund, 2),
                'other' => round($totalInOther, 2),
                'total' => round($totalMonthIn, 2),
            ],
            'out' => [
                'fund' => round($totalOutFund, 2),
                'purchase' => round($totalOutPurchase, 2),
                'supplier_payment' => round($totalOutSupplier, 2),
                'fixed_asset' => round($totalOutFixedAsset, 2),
                'expense' => round($totalOutExpense, 2),
                'other' => round($totalOutOther, 2),
                'total' => round($totalMonthOut, 2),
            ],
        ];

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $locale = $request->input('locale', 'bn');
        $isBn = ($locale === 'bn');

        $pdf = PDF::loadView('pdf.bank-transaction-report', [
            'bankAccount' => $selectedAccount,
            'month' => $isBn ? to_bangla_month($selectedMonth) : ($months[$selectedMonth] ?? $selectedMonth),
            'year' => $isBn ? to_bangla_number($selectedYear) : $selectedYear,
            'previousMonthBalance' => round($previousMonthBalance, 2),
            'dailyTransactions' => $allDates,
            'monthTotals' => $monthTotals,
            'isBn' => $isBn,
            'locale' => $locale,
        ]);

        $pdf->setPaper('A4', 'landscape');

        $accountNameLabel = $selectedAccount ? $selectedAccount->bank_name : 'all-accounts';
        $filename = sprintf('bank-transaction-report-%s-%s-%s.pdf', $accountNameLabel, $selectedMonth, $selectedYear);

        return $pdf->download($filename);
    }
}
