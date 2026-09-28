<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed initial default data for a tenant (shop owner).
     */
    public function run(): void
    {
        // 1. Ensure essential roles exist
        Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'permissions' => null,
            ]
        );

        Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'permissions' => null,
            ]
        );

        Role::firstOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'permissions' => null,
            ]
        );

        // 2. Seed all essential units for this shop owner
        $this->call(UnitSeeder::class);
    }
}
