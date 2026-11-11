<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExtraIncome;
use App\Models\FixedAssetItem;
use App\Models\Fund;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tenant;
use App\Observers\AccountingObserver;
use App\Services\FinancialLedger;
use App\Services\FinancialReport;
use App\Services\InventoryValuation;
use App\Services\SaleAccounting;
use App\Services\SaleAmounts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitializeInventoryValuation extends Command
{
    protected $signature = 'inventory:initialize {--tenant= : Tenant ID; omit only for standalone databases} {--dry-run : Validate and roll back (default)} {--apply : Persist the validated reconstruction}';

    protected $description = 'Reconcile and initialize legacy lots and sale cost snapshots atomically; refuses ambiguous historical links';

    public function handle(): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose --apply or --dry-run, not both.');

            return self::FAILURE;
        }
        $initializedTenant = false;
        $originalTimezone = config('app.timezone');
        $originalPhpTimezone = date_default_timezone_get();
        try {
            if ($this->option('tenant')) {
                tenancy()->initialize(Tenant::findOrFail($this->option('tenant')));
                $initializedTenant = true;
            } elseif (! tenant() && Schema::hasTable('tenants') && DB::table('tenants')->exists()) {
                throw new \LogicException('Specify --tenant to avoid initializing the central database in a multi-tenant installation.');
            }
            $timezone = Setting::get('business_timezone', 'Asia/Dhaka');
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
            DB::beginTransaction();
            app(FinancialLedger::class)->initializing = true;
            $policy = DB::table('inventory_policies')->lockForUpdate()->find(1);
            if ($policy->accounting_ready && ($policy->legacy_cutover_at ?? null)) {
                DB::rollBack();
                $this->info('Legacy stock is already carried forward; no original history was changed.');

                return self::SUCCESS;
            }
            if (! DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
                foreach (['product_stocks', 'sales', 'bank_transactions', 'expenses', 'extra_incomes', 'funds', 'purchases', 'fixed_asset_items'] as $table) {
                    if (DB::table($table)->whereNotNull('deleted_at')->exists()) {
                        throw new \LogicException("{$table}: deleted legacy history requires a reviewed opening-balance cutover or evidence-backed historical reconstruction. Automatic replay will not omit those documents.");
                    }
                }
            }
            if (ProductStock::whereNotNull('costing_method')->exists()) {
                if (ProductStock::whereNull('costing_method')->exists()) {
                    throw new \LogicException('Mixed initialized and legacy entries require manual reconciliation.');
                }
                if (DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
                    DB::rollBack();
                    $this->info('Inventory is already initialized; no data changed.');

                    return self::SUCCESS;
                }
            }
            foreach (SaleItem::orderBy('id')->get()->groupBy('sale_id') as $saleId => $lines) {
                $sale = Sale::withTrashed()->findOrFail($saleId);
                $weights = $lines->pluck('subtotal')->all();
                $amounts = app(SaleAmounts::class);
                $discounts = $amounts->allocate($weights, $sale->discount);
                $nets = array_map(fn ($weight, $discount) => bcsub((string) $weight, $discount, 2), $weights, $discounts);
                $taxes = $amounts->allocate($nets, $sale->tax ?? 0);
                foreach ($lines->values() as $i => $line) {
                    if (! DB::table('sale_accounting_entries')->where('source_type', 'sale')->where('source_id', $line->id)->exists()) {
                        $line->discount_amount = $discounts[$i];
                        $line->tax_amount = $taxes[$i];
                        DB::table('sale_items')->where('id', $line->id)->update(['discount_amount' => $discounts[$i], 'tax_amount' => $taxes[$i]]);
                        app(SaleAccounting::class)->record($line, $sale->business_date ?? $sale->created_at->toDateString());
                    }
                }
            }
            $count = 0;
            foreach (ProductStock::orderByRaw('COALESCE(date, DATE(created_at))')->orderBy('created_at')->orderBy('id')->get() as $stock) {
                if ($stock->costing_method) {
                    continue;
                }
                $date = $stock->date ?? $stock->created_at->toDateString();
                if ($stock->type === 'sale' && ! $stock->sale_item_id) {
                    $matches = SaleItem::where('product_id', $stock->product_id)->where('product_variant_id', $stock->product_variant_id)
                        ->where('quantity', abs($stock->quantity))->whereNotIn('id', ProductStock::whereNotNull('sale_item_id')->select('sale_item_id'))
                        ->whereIn('sale_id', DB::table('sales')->whereDate('created_at', $date)->select('id'))->get();
                    if ($matches->count() !== 1) {
                        throw new \LogicException("Stock #{$stock->id}: original sale line is missing or ambiguous. Resolve source evidence before initialization.");
                    }
                    $stock->sale_item_id = $matches->first()->id;
                }
                if (in_array($stock->type, ['sale_return', 'sale_reversal'], true) && ! $stock->source_stock_id) {
                    throw new \LogicException("Stock #{$stock->id}: return/reversal requires a verified original stock link. No cost was guessed.");
                }
                $allocations = app(InventoryValuation::class)->prepare($stock, initializing: true);
                DB::table('product_stocks')->where('id', $stock->id)->update($stock->getDirty());
                app(InventoryValuation::class)->finish($stock, $allocations);
                $count++;
            }
            if (DB::table('sale_accounting_entries')->whereNull('cost')->exists()) {
                throw new \LogicException('Some physical sale lines have no stock evidence. Reconcile missing stock movements before initialization.');
            }
            // Reconstruct only evidenced source documents, never a balancing plug.
            foreach ([BankAccount::class, BankTransaction::class,
                Supplier::class, Purchase::class, SupplierPayment::class,
                Sale::class, SalePayment::class, Expense::class,
                ExtraIncome::class, Fund::class, FixedAssetItem::class] as $modelClass) {
                $documents = in_array($modelClass, [BankAccount::class, Supplier::class], true) ? $modelClass::withTrashed() : $modelClass::query();
                foreach ($documents->orderBy('id')->get() as $document) {
                    if (($document instanceof Expense || $document instanceof ExtraIncome || $document instanceof Fund) && ! $document->bank_transaction_id) {
                        $category = $document instanceof Expense ? 'expense' : ($document instanceof ExtraIncome ? 'extra_income' : 'fund_'.$document->type);
                        $direction = $document instanceof Expense ? 'out' : ($document instanceof ExtraIncome ? 'in' : $document->type);
                        $matches = BankTransaction::where('bank_account_id', $document->bank_account_id)->where('category', $category)
                            ->where('transaction_type', $direction)->where('amount', $document->amount)->whereDate('date', $document->date)->get();
                        if ($matches->count() !== 1) {
                            throw new \LogicException($document->getTable().' #'.$document->id.': bank transaction link is missing or ambiguous.');
                        }
                        $document->bank_transaction_id = $matches->first()->id;
                        DB::table($document->getTable())->where('id', $document->id)->update(['bank_transaction_id' => $document->bank_transaction_id]);
                    }
                    if ($document instanceof Sale) {
                        $document->paid = bcsub((string) $document->paid, (string) $document->payments()->sum('amount'), 2);
                        if (bccomp((string) $document->paid, '0', 2) < 0) {
                            throw new \LogicException('Sale payment history does not reconcile.');
                        }
                    }
                    app(AccountingObserver::class)->post($document);
                }
            }
            DB::table('inventory_policies')->where('id', 1)->update(['accounting_ready' => true]);
            $sheet = app(FinancialReport::class)->balanceSheet(now()->toDateString());
            foreach ($sheet['reconciliation'] as $name => $difference) {
                if (bccomp((string) $difference, '0', 6) !== 0) {
                    throw new \LogicException("Reconciliation failed: {$name} = {$difference}. No changes persisted.");
                }
            }
            $this->table(['Method', 'Stock entries', 'Inventory value', 'Net earnings', 'Cash'], [[
                DB::table('inventory_policies')->where('id', 1)->value('method'), $count,
                $sheet['property_and_assets']['stock_value']['period'], $sheet['fund_and_liabilities']['net_profit']['period'],
                $sheet['property_and_assets']['bank_balance']['period'],
            ]]);
            if ($this->option('apply')) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            $this->info(($this->option('apply') ? 'Initialized' : 'Validated (rolled back)')." {$count} stock entries. Historical costs are reconstructed under the selected policy; archive legacy reports and review the resulting differences before cutover.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            config(['app.timezone' => $originalTimezone]);
            date_default_timezone_set($originalPhpTimezone);
            app(FinancialLedger::class)->initializing = false;
            if ($initializedTenant) {
                tenancy()->end();
            }
        }
    }
}
