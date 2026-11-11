<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryPolicy
{
    public const METHODS = ['fifo', 'moving_weighted_average'];

    public function current(): object
    {
        app(LegacyInventoryCarryForward::class)->ensure();

        return DB::table('inventory_policies')->find(1)
            ?? throw new \LogicException('Inventory schema is not initialized. Run the tenant migrations.');
    }

    public function select(string $method): void
    {
        if (! in_array($method, self::METHODS, true)) {
            throw ValidationException::withMessages(['inventory_costing_method' => 'Choose FIFO or Moving Weighted Average.']);
        }
        app(LegacyInventoryCarryForward::class)->ensure();
        DB::transaction(function () use ($method) {
            $policy = DB::table('inventory_policies')->lockForUpdate()->find(1);
            if ($policy->method !== $method && ($policy->locked || DB::table('product_stocks')->whereNotNull('costing_method')->where('type', '!=', 'legacy_opening')->exists())) {
                throw ValidationException::withMessages(['inventory_costing_method' => 'Posted inventory locks this method. A reconciled accounting cutover is required to change it.']);
            }
            DB::table('inventory_policies')->where('id', 1)->update(['method' => $method, 'updated_at' => now()]);
            DB::table('product_stocks')->where('type', 'legacy_opening')->update(['costing_method' => $method]);
        });
    }
}
