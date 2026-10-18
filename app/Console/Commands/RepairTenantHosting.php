<?php

namespace App\Console\Commands;

use App\Http\Controllers\Central\TenantRegistrationController;
use App\Services\TenantDatabaseService;
use Illuminate\Console\Command;

class RepairTenantHosting extends Command
{
    protected $signature = 'tenants:repair-hosting';

    protected $description = 'Drop orphaned tenant databases and sync tenant domains after APP_URL / CENTRAL_DOMAIN changes';

    public function handle(TenantDatabaseService $databases): int
    {
        $baseDomain = TenantRegistrationController::resolveBaseDomain();
        $this->info("Central host: {$baseDomain}");

        $dropped = $databases->dropOrphanedDatabases();
        if ($dropped === []) {
            $this->info('No orphaned tenant databases found.');
        } else {
            foreach ($dropped as $name) {
                $this->warn("Dropped leftover database: {$name}");
            }
        }

        $updated = $databases->syncTenantDomains($baseDomain);
        $this->info("Tenant domains synced ({$updated} change(s)).");

        $this->call('config:clear');
        $this->call('cache:clear');

        return self::SUCCESS;
    }
}
