<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Tenant;
use App\Services\FinancialReport;
use App\Services\InventoryPolicy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeInventoryMethod extends Command
{
    protected $signature = 'inventory:change-method {method} {--tenant=} {--date= : Effective date at the start of an open day} {--reason= : Reviewed accounting justification} {--apply : Otherwise preview and roll back}';

    protected $description = 'Preview or apply a reconciled prospective costing cutover while preserving historical costs';

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
            $method = $this->argument('method');
            if (! in_array($method, InventoryPolicy::METHODS, true)) {
                throw new \InvalidArgumentException('Choose fifo or moving_weighted_average.');
            }
            if (! $this->option('date') || ! $this->option('reason')) {
                throw new \InvalidArgumentException('Provide --date and --reason after reviewing the cutover.');
            }
            $date = Carbon::createFromFormat('!Y-m-d', $this->option('date'))->toDateString();
            if ($date !== $this->option('date') || $date > now()->toDateString()) {
                throw new \InvalidArgumentException('Use a valid effective date no later than today.');
            }
            DB::beginTransaction();
            $policy = DB::table('inventory_policies')->lockForUpdate()->find(1);
            if (! $policy->accounting_ready || $policy->method === $method) {
                throw new \LogicException('Initialize accounting and choose a different method.');
            }
            if (($policy->effective_from && $date <= $policy->effective_from) || DB::table('ledger_lines')->where('date', '>=', $date)->exists()) {
                throw new \LogicException('Choose an unused accounting day after all posted activity.');
            }
            $sheet = app(FinancialReport::class)->balanceSheet($date);
            foreach ($sheet['reconciliation'] as $difference) {
                if (bccomp((string) $difference, '0', 6) !== 0) {
                    throw new \LogicException('Resolve all accounting differences before changing the method.');
                }
            }
            $balances = DB::table('inventory_balances')->lockForUpdate()->get();
            if ($method === 'fifo') {
                foreach ($balances as $balance) {
                    $lots = DB::table('product_stocks')->where('product_id', $balance->product_id)
                        ->where('product_variant_id', $balance->variant_key ?: null)->where('lot_remaining_quantity', '>', 0)->orderBy('date')->orderBy('id')->lockForUpdate()->get();
                    $allocated = '0';
                    foreach ($lots as $i => $lot) {
                        $value = $i === $lots->count() - 1 ? bcsub((string) $balance->value, $allocated, 6)
                            : bcmul(bcdiv((string) $balance->value, (string) $balance->quantity, 12), (string) $lot->lot_remaining_quantity, 6);
                        DB::table('product_stocks')->where('id', $lot->id)->update(['lot_remaining_value' => $value]);
                        $allocated = bcadd($allocated, $value, 6);
                    }
                }
            }
            DB::table('inventory_policy_changes')->insert([
                'old_method' => $policy->method, 'new_method' => $method, 'effective_from' => $date,
                'reason' => $this->option('reason'), 'opening_balances' => $balances->toJson(), 'created_at' => now(),
            ]);
            DB::table('inventory_policies')->where('id', 1)->update([
                'method' => $method, 'effective_from' => $date, 'locked_through' => Carbon::parse($date)->subDay()->toDateString(), 'updated_at' => now(),
            ]);
            if ($this->option('apply')) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            $this->info(($this->option('apply') ? 'Applied' : 'Previewed (rolled back)')." {$policy->method} → {$method} on {$date}; total carrying value unchanged. Review statutory reporting treatment separately.");

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
