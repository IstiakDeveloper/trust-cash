<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait PostsAccountingAtomically
{
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }
}
