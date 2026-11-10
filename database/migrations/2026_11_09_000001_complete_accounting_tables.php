<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The standalone schema historically omitted modules present in tenant schemas.
        foreach ([
            'suppliers' => '2026_09_27_000001_create_suppliers_table.php',
            'supplier_payments' => '2026_09_27_000002_create_supplier_payments_table.php',
            'purchases' => '2026_09_27_000003_create_purchases_table.php',
            'purchase_items' => '2026_09_27_000004_create_purchase_items_table.php',
            'sale_returns' => '2026_09_27_000005_create_sale_returns_table.php',
            'branches' => '2026_09_27_000007_create_branches_table.php',
            'fixed_assets' => '2026_09_28_000001_create_fixed_assets_table.php',
            'fixed_asset_items' => '2026_09_28_000002_create_fixed_asset_items_table.php',
        ] as $table => $file) {
            if (! Schema::hasTable($table)) {
                (require __DIR__.'/tenant/'.$file)->up();
            }
        }
    }

    public function down(): void
    {
        // Never drop potentially pre-existing business tables on rollback.
    }
};
