<?php

namespace App\Services;

use App\Http\Controllers\Central\TenantRegistrationController;
use App\Models\Tenant;
use App\Support\TenancyHost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantDatabaseService
{
    public function databaseNameFor(string $tenantId): string
    {
        return config('tenancy.database.prefix', 'tenant')
            . $tenantId
            . config('tenancy.database.suffix', '');
    }

    /**
     * Drop a tenant MySQL database.
     * When $onlyOrphan is true, never touch a database that still has a tenants row.
     */
    public function dropIfExists(string $tenantId, bool $onlyOrphan = true): void
    {
        if ($onlyOrphan && Tenant::query()->whereKey($tenantId)->exists()) {
            return;
        }

        $database = $this->sanitizeName($this->databaseNameFor($tenantId));
        $central = (string) config('database.connections.'.config('tenancy.database.central_connection', 'mysql').'.database');

        if ($database === '' || $database === $central) {
            return;
        }

        try {
            DB::connection(config('tenancy.database.central_connection'))
                ->statement("DROP DATABASE IF EXISTS `{$database}`");
        } catch (\Throwable $e) {
            Log::warning("Failed to drop tenant database {$database}: ".$e->getMessage());
        }
    }

    /**
     * Drop tenant-prefixed databases that have no matching tenants row.
     *
     * @return list<string>
     */
    public function dropOrphanedDatabases(): array
    {
        $prefix = (string) config('tenancy.database.prefix', 'tenant');
        $central = (string) config('database.connections.'.config('tenancy.database.central_connection', 'mysql').'.database');
        $expected = Tenant::query()->pluck('id')
            ->map(fn ($id) => $this->databaseNameFor((string) $id))
            ->all();

        $schemas = DB::select(
            'SELECT SCHEMA_NAME AS name FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME LIKE ?',
            [$prefix.'%']
        );

        $dropped = [];
        foreach ($schemas as $row) {
            $name = (string) $row->name;
            if ($name === $central || in_array($name, $expected, true)) {
                continue;
            }

            $safe = $this->sanitizeName($name);
            if ($safe === '' || $safe === $central) {
                continue;
            }

            DB::statement("DROP DATABASE IF EXISTS `{$safe}`");
            $dropped[] = $safe;
        }

        return $dropped;
    }

    /**
     * Point every tenant at {id}.{current-base-domain} and remove leftover IP/URL domains.
     */
    public function syncTenantDomains(?string $baseDomain = null): int
    {
        $baseDomain = TenancyHost::from($baseDomain) ?: TenantRegistrationController::resolveBaseDomain();
        $updated = 0;

        foreach (Tenant::with('domains')->get() as $tenant) {
            $correct = TenancyHost::shopHost($tenant->id, $baseDomain);
            $hasCorrect = $tenant->domains->contains(fn ($domain) => $domain->domain === $correct);

            if (!$hasCorrect) {
                $tenant->domains()->create(['domain' => $correct]);
                $updated++;
            }

            foreach ($tenant->domains as $domain) {
                if ($domain->domain === $correct) {
                    continue;
                }
                if (in_array($domain->domain, [$tenant->id.'.localhost', $tenant->id.'.127.0.0.1'], true)) {
                    continue;
                }
                if ($this->isStaleDomain($domain->domain, $baseDomain)) {
                    $domain->delete();
                    $updated++;
                }
            }
        }

        return $updated;
    }

    public function rollbackFailedProvision(string $tenantId, ?Tenant $tenant = null): void
    {
        $this->dropIfExists($tenantId, onlyOrphan: false);

        $tenant ??= Tenant::query()->find($tenantId);
        if (!$tenant) {
            return;
        }

        try {
            Tenant::withoutEvents(function () use ($tenant) {
                $tenant->domains()->delete();
                $tenant->subscriptions()->delete();
                $tenant->delete();
            });
        } catch (\Throwable $e) {
            Log::warning("Failed to roll back tenant {$tenantId}: ".$e->getMessage());
        }
    }

    private function isStaleDomain(string $domain, string $baseDomain): bool
    {
        if (TenancyHost::isMalformedShopDomain($domain)) {
            return true;
        }
        if (str_ends_with($domain, '.nip.io')) {
            return true;
        }
        if (preg_match('/\.\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $domain)) {
            return true;
        }

        $host = explode('.', $domain, 2)[1] ?? '';

        return $host !== '' && $host !== $baseDomain;
    }

    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '', $name) ?? '';
    }
}
