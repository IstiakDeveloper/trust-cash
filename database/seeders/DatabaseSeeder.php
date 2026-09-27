<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'permissions' => null,
            ]
        );

        // Create the Super Admin user
        User::firstOrCreate(
            ['email' => 'admin@trustcash.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'phone' => null,
                'address' => null,
                'status' => 1,
            ]
        );

        $this->call([
            PlanSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
