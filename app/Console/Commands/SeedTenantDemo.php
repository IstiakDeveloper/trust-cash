<?php

namespace App\Console\Commands;

use App\Http\Controllers\Central\TenantRegistrationController;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenancyHost;
use Database\Seeders\TenantDemoSeeder;
use Illuminate\Console\Command;

class SeedTenantDemo extends Command
{
    protected $signature = 'tenants:seed-demo {tenant=demo : Tenant id / subdomain} {--force : Seed even if demo data already exists}';

    protected $description = 'Create (if needed) one tenant shop and fill a small faker demo: funds, purchase, sale, due customers';

    public function handle(): int
    {
        $tenantId = strtolower((string) $this->argument('tenant'));

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $plan = Plan::query()->where('is_active', true)->orderBy('sort_order')->first();
            if (! $plan) {
                $this->error('No plan found. Run `php artisan db:seed` on the central database first.');

                return self::FAILURE;
            }

            $this->info("Creating tenant [{$tenantId}]...");
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => 'Demo Super Shop',
                'email' => $tenantId.'@demo.trustcash.local',
                'status' => 'active',
                'plan_id' => $plan->id,
                'trial_ends_at' => now()->addDays(30),
            ]);

            $host = TenantRegistrationController::tenantFqdn($tenantId);
            $tenant->domains()->firstOrCreate(['domain' => $host]);
        }

        tenancy()->initialize($tenant);

        if ($this->option('force')) {
            \App\Models\Setting::set('demo_data_seeded', '0');
        }

        $this->info("Seeding small demo data into [{$tenant->id}]...");
        $seeder = app(TenantDemoSeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        $admin = User::query()->orderBy('id')->first();
        $username = $admin?->username ?: $tenant->id;
        $shopUrl = TenancyHost::shopUrl($tenant->id);

        $this->newLine();
        $this->line('========================================');
        $this->info('  TENANT LOGIN');
        $this->line('========================================');
        $this->line('  Shop     : '.$tenant->name);
        $this->line('  URL      : '.$shopUrl.'/login');
        $this->line('  Username : '.$username);
        $this->line('  Password : '.TenantDemoSeeder::DEMO_PASSWORD);
        $this->line('========================================');
        $this->newLine();
        $this->comment('Includes: opening fund, 6 products, 2 purchases, 4 customers, paid/partial/due sales, 2 expenses.');

        tenancy()->end();

        return self::SUCCESS;
    }
}
