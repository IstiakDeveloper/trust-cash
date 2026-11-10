<?php

namespace App\Console\Commands;

use App\Models\BankTransaction;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\CashClassification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassifyCashTransaction extends Command
{
    protected $signature = 'accounting:classify-cash {transaction} {account} {--tenant=} {--reason=} {--apply : Otherwise preview and roll back}';

    protected $description = 'Classify an evidenced standalone cash movement before legacy reconciliation';

    public function handle(): int
    {
        $initialized = false;
        $originalTimezone = config('app.timezone');
        $originalPhpTimezone = date_default_timezone_get();
        try {
            if ($this->option('tenant')) {
                tenancy()->initialize(Tenant::findOrFail($this->option('tenant')));
                $initialized = true;
            } elseif (! tenant() && Schema::hasTable('tenants') && DB::table('tenants')->exists()) {
                throw new \LogicException('Specify --tenant.');
            }
            $timezone = Setting::get('business_timezone', 'Asia/Dhaka');
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
            $account = $this->argument('account');
            if (! in_array($account, CashClassification::ACCOUNTS, true) || ! $this->option('reason')) {
                throw new \LogicException('Provide a supported account and an evidence-based --reason.');
            }
            DB::beginTransaction();
            DB::table('inventory_policies')->lockForUpdate()->find(1);
            $transaction = BankTransaction::whereKey($this->argument('transaction'))->lockForUpdate()->firstOrFail();
            if ($transaction->counter_account) {
                throw new \LogicException('This transaction is already classified. Use a dated correction.');
            }
            foreach (['expenses', 'extra_incomes', 'funds', 'fixed_asset_items'] as $table) {
                if (DB::table($table)->where('bank_transaction_id', $transaction->id)->exists()) {
                    throw new \LogicException('This movement belongs to a source document; reconcile that document instead.');
                }
            }
            $data = ['counter_account' => $account, 'classification_reason' => $this->option('reason'), 'category' => CashClassification::category($account, $transaction->transaction_type)];
            if (DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
                $transaction->update($data);
            } else {
                DB::table('bank_transactions')->where('id', $transaction->id)->update($data);
            }
            if ($this->option('apply')) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            $this->info(($this->option('apply') ? 'Classified' : 'Previewed (rolled back)').' cash movement #'.$transaction->id.' as '.$account.'.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            config(['app.timezone' => $originalTimezone]);
            date_default_timezone_set($originalPhpTimezone);
            if ($initialized) {
                tenancy()->end();
            }
        }
    }
}
