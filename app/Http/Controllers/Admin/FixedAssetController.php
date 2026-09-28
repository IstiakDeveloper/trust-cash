<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\FixedAsset;
use App\Models\FixedAssetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = FixedAsset::with([
            'items' => function ($q) use ($request) {
                $q->when($request->status, fn ($sub, $status) => $sub->where('status', $status))
                  ->when($request->from_date, fn ($sub, $from) => $sub->whereDate('purchase_date', '>=', $from))
                  ->when($request->to_date, fn ($sub, $to) => $sub->whereDate('purchase_date', '<=', $to))
                  ->with('bankAccount')
                  ->orderBy('purchase_date', 'desc');
            },
            'branch',
            'createdBy'
        ]);

        // Apply filters
        $filteredAssets = $query
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('asset_code', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhereHas('items', function ($itemQ) use ($search) {
                            $itemQ->where('item_name', 'LIKE', "%{$search}%")
                                  ->orWhere('description', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->when($request->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->bank_account_id, function ($q, $bankId) {
                $q->whereHas('items', fn ($itemQ) => $itemQ->where('bank_account_id', $bankId));
            });

        $assets = $filteredAssets
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Summary calculations
        $totalAssetsCount = FixedAsset::count();
        $totalItemsCount = FixedAssetItem::count();
        $totalPurchaseValue = (float) FixedAssetItem::sum('purchase_price');
        $activeAssetsValue = (float) FixedAssetItem::where('status', 'active')->sum('current_value');

        return Inertia::render('Admin/FixedAssets/Index', [
            'assets' => $assets,
            'next_code' => $this->getNextAssetCode(),
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'branches' => Branch::where('status', true)->get(),
            'filters' => $request->only(['search', 'status', 'bank_account_id', 'branch_id', 'from_date', 'to_date']),
            'summary' => [
                'total_assets_count' => $totalAssetsCount,
                'total_items_count' => $totalItemsCount,
                'total_purchase_value' => $totalPurchaseValue,
                'active_value' => $activeAssetsValue,
            ],
        ]);
    }

    private function getNextAssetCode(): string
    {
        $maxCodeNumber = 0;
        $codes = FixedAsset::withTrashed()->pluck('asset_code');
        foreach ($codes as $code) {
            if ($code && preg_match('/FA-(\d+)/i', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxCodeNumber) {
                    $maxCodeNumber = $num;
                }
            }
        }
        return 'FA-' . str_pad($maxCodeNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:50|unique:fixed_assets,asset_code',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'nullable|in:active,disposed',
            'description' => 'nullable|string',

            // Initial item details (optional)
            'item_name' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|required_with:item_name|date',
            'purchase_price' => 'nullable|required_with:item_name|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'item_description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Auto-generate 4-digit asset code if not provided
            if (empty($validated['asset_code'])) {
                $validated['asset_code'] = $this->getNextAssetCode();
            }

            $validated['status'] = $validated['status'] ?? 'active';
            $validated['created_by'] = auth()->id();

            $asset = FixedAsset::create([
                'name' => $validated['name'],
                'asset_code' => $validated['asset_code'],
                'branch_id' => $validated['branch_id'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // If initial item is provided, create it
            if (!empty($validated['item_name'])) {
                $purchasePrice = (float) $validated['purchase_price'];
                $currentValue = isset($validated['current_value']) && $validated['current_value'] !== null
                    ? (float) $validated['current_value']
                    : $purchasePrice;

                $bankTxId = null;

                if (!empty($validated['bank_account_id']) && $purchasePrice > 0) {
                    $bankAccount = BankAccount::find($validated['bank_account_id']);
                    if ($bankAccount && $bankAccount->current_balance < $purchasePrice) {
                        return redirect()->back()->withErrors([
                            'bank_account_id' => 'নির্বাচিত ব্যাংক অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই। বর্তমান ব্যালেন্স: ' . number_format($bankAccount->current_balance, 2)
                        ])->withInput();
                    }

                    $transaction = BankTransaction::create([
                        'bank_account_id' => $validated['bank_account_id'],
                        'transaction_type' => 'out',
                        'amount' => $purchasePrice,
                        'description' => "Fixed Asset: {$validated['item_name']} ({$asset->name})",
                        'date' => $validated['purchase_date'],
                        'created_by' => auth()->id(),
                    ]);

                    $bankTxId = $transaction->id;
                }

                FixedAssetItem::create([
                    'fixed_asset_id' => $asset->id,
                    'item_name' => $validated['item_name'],
                    'purchase_date' => $validated['purchase_date'],
                    'purchase_price' => $purchasePrice,
                    'current_value' => $currentValue,
                    'bank_account_id' => $validated['bank_account_id'] ?? null,
                    'bank_transaction_id' => $bankTxId,
                    'status' => 'active',
                    'description' => $validated['item_description'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'স্থায়ী সম্পদ সফলভাবে যুক্ত করা হয়েছে');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'সম্পদ তৈরি করতে ব্যর্থ হয়েছে: ' . $e->getMessage());
        }
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:50|unique:fixed_assets,asset_code,' . $fixedAsset->id,
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'nullable|in:active,disposed',
            'description' => 'nullable|string',
        ]);

        $fixedAsset->update($validated);

        return redirect()->back()->with('success', 'স্থায়ী সম্পদের তথ্য আপডেট করা হয়েছে');
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        DB::beginTransaction();
        try {
            // Delete all child items and rollback their bank transactions
            foreach ($fixedAsset->items as $item) {
                if ($item->bank_transaction_id) {
                    BankTransaction::find($item->bank_transaction_id)?->delete();
                }
                $item->delete();
            }

            $fixedAsset->delete();

            DB::commit();

            return redirect()->back()->with('success', 'স্থায়ী সম্পদ এবং এর অন্তর্ভুক্ত সকল আইটেম মুছে ফেলা হয়েছে');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'সম্পদ মুছতে ব্যর্থ হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Add a new item / component under an existing Fixed Asset
     */
    public function storeItem(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'status' => 'nullable|in:active,disposed,damaged',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $purchasePrice = (float) $validated['purchase_price'];
            $currentValue = isset($validated['current_value']) && $validated['current_value'] !== null
                ? (float) $validated['current_value']
                : $purchasePrice;

            $bankTxId = null;

            if (!empty($validated['bank_account_id']) && $purchasePrice > 0) {
                $bankAccount = BankAccount::find($validated['bank_account_id']);
                if ($bankAccount && $bankAccount->current_balance < $purchasePrice) {
                    return redirect()->back()->withErrors([
                        'bank_account_id' => 'নির্বাচিত ব্যাংক অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই। বর্তমান ব্যালেন্স: ' . number_format($bankAccount->current_balance, 2)
                    ])->withInput();
                }

                $transaction = BankTransaction::create([
                    'bank_account_id' => $validated['bank_account_id'],
                    'transaction_type' => 'out',
                    'amount' => $purchasePrice,
                    'description' => "Fixed Asset: {$validated['item_name']} ({$fixedAsset->name})",
                    'date' => $validated['purchase_date'],
                    'created_by' => auth()->id(),
                ]);

                $bankTxId = $transaction->id;
            }

            FixedAssetItem::create([
                'fixed_asset_id' => $fixedAsset->id,
                'item_name' => $validated['item_name'],
                'purchase_date' => $validated['purchase_date'],
                'purchase_price' => $purchasePrice,
                'current_value' => $currentValue,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'bank_transaction_id' => $bankTxId,
                'status' => $validated['status'] ?? 'active',
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', "{$fixedAsset->name} এর অধীনে নতুন আইটেম যুক্ত করা হয়েছে");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'আইটেম যোগ করতে ব্যর্থ হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Update an individual item under a Fixed Asset
     */
    public function updateItem(Request $request, FixedAssetItem $item)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'status' => 'nullable|in:active,disposed,damaged',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $purchasePrice = (float) $validated['purchase_price'];
            $currentValue = isset($validated['current_value']) && $validated['current_value'] !== null
                ? (float) $validated['current_value']
                : $purchasePrice;

            $assetName = $item->asset?->name ?? 'Asset';

            // Handle Bank Transaction syncing
            if (!empty($validated['bank_account_id']) && $purchasePrice > 0) {
                if ($item->bank_transaction_id) {
                    $transaction = BankTransaction::find($item->bank_transaction_id);
                    if ($transaction) {
                        $transaction->update([
                            'bank_account_id' => $validated['bank_account_id'],
                            'transaction_type' => 'out',
                            'amount' => $purchasePrice,
                            'description' => "Fixed Asset: {$validated['item_name']} ({$assetName})",
                            'date' => $validated['purchase_date'],
                        ]);
                    }
                } else {
                    $bankAccount = BankAccount::find($validated['bank_account_id']);
                    if ($bankAccount && $bankAccount->current_balance < $purchasePrice) {
                        return redirect()->back()->withErrors([
                            'bank_account_id' => 'নির্বাচিত ব্যাংক অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই। বর্তমান ব্যালেন্স: ' . number_format($bankAccount->current_balance, 2)
                        ])->withInput();
                    }

                    $transaction = BankTransaction::create([
                        'bank_account_id' => $validated['bank_account_id'],
                        'transaction_type' => 'out',
                        'amount' => $purchasePrice,
                        'description' => "Fixed Asset: {$validated['item_name']} ({$assetName})",
                        'date' => $validated['purchase_date'],
                        'created_by' => auth()->id(),
                    ]);

                    $validated['bank_transaction_id'] = $transaction->id;
                }
            } else {
                if ($item->bank_transaction_id) {
                    BankTransaction::find($item->bank_transaction_id)?->delete();
                    $validated['bank_transaction_id'] = null;
                }
            }

            $validated['current_value'] = $currentValue;
            $item->update($validated);

            DB::commit();

            return redirect()->back()->with('success', 'আইটেমের তথ্য সফলভাবে আপডেট করা হয়েছে');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'আইটেম আপডেট করতে ব্যর্থ হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Delete an individual item under a Fixed Asset
     */
    public function destroyItem(FixedAssetItem $item)
    {
        DB::beginTransaction();
        try {
            if ($item->bank_transaction_id) {
                BankTransaction::find($item->bank_transaction_id)?->delete();
            }

            $item->delete();

            DB::commit();

            return redirect()->back()->with('success', 'আইটেমটি সফলভাবে মুছে ফেলা হয়েছে');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'আইটেম মুছতে ব্যর্থ হয়েছে: ' . $e->getMessage());
        }
    }
}
