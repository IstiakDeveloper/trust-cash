<?php

namespace App\Listeners;

use App\Services\TenantDatabaseService;
use Stancl\Tenancy\Events\CreatingTenant;

class DropOrphanedTenantDatabase
{
    public function handle(CreatingTenant $event): void
    {
        $tenantId = (string) $event->tenant->id;

        app(TenantDatabaseService::class)->dropIfExists($tenantId, onlyOrphan: true);
    }
}
