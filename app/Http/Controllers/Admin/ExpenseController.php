<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'bankAccount', 'createdBy']);

        // Apply filters
        $filteredExpenses = $query
            ->when($request->category_id, fn ($q) => $q->where('expense_category_id', $request->category_id))
            ->when($request->bank_id, fn ($q) => $q->where('bank_account_id', $request->bank_id))
            ->when($request->from_date, fn ($q) => $q->whereDate('date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('date', '<=', $request->to_date));

        // Pagination
        $expenses = $filteredExpenses
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Calculate summary
        $totalExpenses = $filteredExpenses->sum('amount');

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::where('status', true)->get(),
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'filters' => $request->only(['category_id', 'bank_id', 'from_date', 'to_date']),
            'summary' => [
                'totalExpenses' => $totalExpenses,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'date' => 'required|date',
            'attachment' => 'nullable|file|max:2048',
        ]);

        DB::beginTransaction();
        try {
            // Handle file upload if exists
            if ($request->hasFile('attachment')) {
                $validated['attachment'] = $request->file('attachment')
                    ->store('expenses/attachments', 'public');
            }

            $validated['created_by'] = auth()->id();

            // Create expense
            $expense = Expense::create($validated);

            // Create bank transaction (Observer automatically deducts from bank account balance)
            BankTransaction::create([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => 'out',
                'amount' => $validated['amount'],
                'description' => "Expense: {$validated['description']}",
                'date' => $validated['date'],
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Expense created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create expense');
        }
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'date' => 'required|date',
            'attachment' => 'nullable|file|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $oldAmount = $expense->amount;
            $oldBankAccountId = $expense->bank_account_id;

            // Handle file upload if exists
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($expense->attachment) {
                    Storage::disk('public')->delete($expense->attachment);
                }
                $validated['attachment'] = $request->file('attachment')
                    ->store('expenses/attachments', 'public');
            }

            // Find the related bank transaction
            $bankTransaction = BankTransaction::where([
                'bank_account_id' => $oldBankAccountId,
                'transaction_type' => 'out',
                'amount' => $oldAmount,
                'date' => $expense->date,
            ])->where('description', 'LIKE', 'Expense: '.$expense->description)
                ->first();

            // Update expense
            $expense->update($validated);

            // Handle bank transaction (Observer handles balance changes)
            if ($bankTransaction) {
                $bankTransaction->update([
                    'bank_account_id' => $validated['bank_account_id'],
                    'transaction_type' => 'out',
                    'amount' => $validated['amount'],
                    'description' => "Expense: {$validated['description']}",
                    'date' => $validated['date'],
                ]);
            } else {
                BankTransaction::create([
                    'bank_account_id' => $validated['bank_account_id'],
                    'transaction_type' => 'out',
                    'amount' => $validated['amount'],
                    'description' => "Expense: {$validated['description']}",
                    'date' => $validated['date'],
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Expense updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to update expense');
        }
    }

    public function destroy(Expense $expense)
    {
        DB::beginTransaction();
        try {
            // Find and soft delete bank transaction (Observer automatically restores bank balance)
            $bankTransaction = BankTransaction::where([
                'bank_account_id' => $expense->bank_account_id,
                'transaction_type' => 'out',
                'amount' => $expense->amount,
                'date' => $expense->date,
            ])->where('description', 'LIKE', 'Expense: '.$expense->description)
                ->first();

            if ($bankTransaction) {
                $bankTransaction->delete();
            }

            // Soft delete expense
            $expense->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Expense deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to delete expense');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $expense = Expense::withTrashed()->findOrFail($id);

            // Restore bank transaction (Observer automatically re-deducts bank balance)
            $bankTransaction = BankTransaction::withTrashed()->where([
                'bank_account_id' => $expense->bank_account_id,
                'transaction_type' => 'out',
                'amount' => $expense->amount,
                'date' => $expense->date,
            ])->where('description', 'LIKE', 'Expense: '.$expense->description)
                ->first();

            if ($bankTransaction) {
                $bankTransaction->restore();
            }

            // Restore expense
            $expense->restore();

            DB::commit();

            return redirect()->back()->with('success', 'Expense restored successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to restore expense');
        }
    }
}
