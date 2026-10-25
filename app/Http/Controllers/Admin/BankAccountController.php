<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::all();

        return Inertia::render('Admin/BankAccounts/Index', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/BankAccounts/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'account_name' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
            'branch_name' => 'nullable',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'nullable|numeric',
            'status' => 'boolean',
        ]);

        $openingBalance = (float) $validatedData['opening_balance'];
        $currentBalance = (float) ($validatedData['current_balance'] ?? 0);
        // Before any transaction, the cash in hand is the opening balance.
        // If only Current Balance was filled, that figure is the opening capital.
        if ($openingBalance == 0.0 && $currentBalance != 0.0) {
            $openingBalance = $currentBalance;
        }
        $validatedData['opening_balance'] = $openingBalance;
        $validatedData['current_balance'] = $openingBalance;

        BankAccount::create($validatedData);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account created successfully.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return Inertia::render('Admin/BankAccounts/Edit', [
            'bankAccount' => $bankAccount,
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validatedData = $request->validate([
            'account_name' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
            'branch_name' => 'nullable',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'nullable|numeric',
            'status' => 'boolean',
        ]);

        $oldOpening = round((float) $bankAccount->opening_balance, 2);
        $newOpening = round((float) $validatedData['opening_balance'], 2);
        $validatedData['opening_balance'] = $newOpening;

        if ($newOpening !== $oldOpening) {
            $moneyIn = (float) $bankAccount->transactions()->where('transaction_type', 'in')->sum('amount');
            $moneyOut = (float) $bankAccount->transactions()->where('transaction_type', 'out')->sum('amount');
            // Current cash is the new starting amount plus every later movement.
            $validatedData['current_balance'] = $newOpening + $moneyIn - $moneyOut;
        } else {
            unset($validatedData['current_balance']);
        }

        $bankAccount->update($validatedData);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account deleted successfully.');
    }
}
