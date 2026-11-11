<?php

namespace App\Traits;

use App\Services\LegacyInventoryCarryForward;
use Illuminate\Support\Facades\DB;

trait PostsAccountingAtomically
{
    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            app(LegacyInventoryCarryForward::class)->ensure();

            return parent::save($options);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            app(LegacyInventoryCarryForward::class)->ensure();

            return parent::delete();
        });
    }
}
