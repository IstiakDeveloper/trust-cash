<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Tenant;
use App\Services\InventoryReport;
use App\Services\LegacyInventoryCarryForward;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CarryForwardLegacyInventory extends Command
{
    protected $signature = 'inventory:carry-forward {--tenant=} {--all-tenants} {--dry-run} {--apply}';

    protected $description = 'Preserve existing stock, averages and balances as opening lots; preview by default';

    public function handle(): int
    {
        if (($this->option('tenant') && $this->option('all-tenants')) || ($this->option('apply') && $this->option('dry-run'))) {
            $this->error('Choose one tenant scope and either preview or --apply.');

            return self::FAILURE;
        }
        if (tenancy()->initialized) {
            $this->error('Run this command from the central application, outside an active tenant context.');

            return self::FAILURE;
        }
        $ids = $this->option('all-tenants') ? Tenant::query()->pluck('id')->all()
            : ($this->option('tenant') ? [$this->option('tenant')] : [null]);
        if ($this->option('all-tenants') && $ids === []) {
            $ids = [null];
        }
        if ($ids === [null] && Schema::hasTable('tenants') && Tenant::query()->exists()) {
            $this->error('Specify --tenant or --all-tenants. The central database was not changed.');

            return self::FAILURE;
        }
        $failed = false;
        foreach ($ids as $id) {
            $timezone = config('app.timezone');
            $phpTimezone = date_default_timezone_get();
            $transactionStarted = false;
            try {
                if ($id !== null) {
                    $tenant = Tenant::findOrFail($id);
                    if (! $tenant->database()->manager()->databaseExists($tenant->database()->getName())) {
                        throw new \LogicException('Tenant database does not exist on this server; restore or connect its database first.');
                    }
                    tenancy()->initialize($tenant);
                }
                $businessTimezone = Setting::get('business_timezone', 'Asia/Dhaka');
                config(['app.timezone' => $businessTimezone]);
                date_default_timezone_set($businessTimezone);
                DB::beginTransaction();
                $transactionStarted = true;
                app(LegacyInventoryCarryForward::class)->ensure();
                $balances = DB::table('inventory_balances')->get();
                $sum = app(InventoryReport::class);
                $this->line(($id ?? 'Standalone').' | '.$balances->count().' product/variant balances | quantity '.$sum->sum($balances, 'quantity').' | value '.$sum->sum($balances, 'value'));
                if ($this->option('apply')) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            } catch (\Throwable $exception) {
                if ($transactionStarted && DB::transactionLevel()) {
                    DB::rollBack();
                }
                $failed = true;
                $this->error(($id ?? 'Standalone').': '.$exception->getMessage());
            } finally {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($phpTimezone);
                if ($id !== null && tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }
        $this->info($this->option('apply') ? 'Carry-forward applied; original records retained.' : 'Preview complete; all changes rolled back.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
