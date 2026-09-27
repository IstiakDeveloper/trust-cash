<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query()
            ->withSum('purchases as total_purchases', 'total_amount')
            ->withSum('purchases as total_due', 'due_amount');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        $bankAccounts = BankAccount::active()
            ->select('id', 'bank_name', 'account_number', 'current_balance')
            ->get();

        $stats = [
            'total_suppliers' => Supplier::count(),
            'total_payable' => Supplier::sum('current_balance'),
        ];

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers,
            'bankAccounts' => $bankAccounts,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['current_balance'] = $validated['opening_balance'];
        $validated['status'] = $validated['status'] ?? 'active';

        Supplier::create($validated);

        return redirect()->back()->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load([
            'purchases' => function ($q) {
                $q->latest()->take(20);
            },
            'payments' => function ($q) {
                $q->with('bankAccount')->latest()->take(20);
            }
        ]);

        $bankAccounts = BankAccount::active()
            ->select('id', 'bank_name', 'account_number', 'current_balance')
            ->get();

        return Inertia::render('Admin/Suppliers/Show', [
            'supplier' => $supplier,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $supplier->update($validated);

        return redirect()->back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete supplier with existing purchase records.');
        }

        $supplier->delete();

        return redirect()->back()->with('success', 'Supplier deleted successfully.');
    }

    public function addPayment(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank,mobile,check',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $payment = SupplierPayment::create([
                'supplier_id' => $supplier->id,
                'bank_account_id' => $validated['bank_account_id'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'reference_no' => $validated['reference_no'] ?? null,
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Deduct from supplier payable balance
            $supplier->decrement('current_balance', $validated['amount']);

            // Deduct from bank account balance
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            $bankAccount->decrement('current_balance', $validated['amount']);

            // Create bank transaction record
            BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'transaction_type' => 'out',
                'amount' => $validated['amount'],
                'description' => "Supplier Payment to: {$supplier->name}" . ($validated['reference_no'] ? " (Ref: {$validated['reference_no']})" : ''),
                'date' => $validated['payment_date'],
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Payment of ৳' . number_format($validated['amount'], 2) . ' recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }
}
