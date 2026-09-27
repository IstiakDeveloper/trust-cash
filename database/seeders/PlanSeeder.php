<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small shops, single-counter retail stores and startups.',
                'price_monthly' => 799.00,
                'price_yearly' => 7990.00,
                'max_users' => 2,
                'max_products' => 500,
                'max_branches' => 1,
                'features' => [
                    'pos',
                    'inventory',
                    'sales',
                    'customers',
                    'cash_register',
                    'basic_accounting',
                    'daily_reports',
                    'barcode_scanner',
                    'receipt_printer',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Ideal for growing businesses, multi-store shops and wholesale traders.',
                'price_monthly' => 1999.00,
                'price_yearly' => 19990.00,
                'max_users' => 6,
                'max_products' => 5000,
                'max_branches' => 3,
                'features' => [
                    'pos',
                    'inventory',
                    'sales',
                    'customers',
                    'suppliers',
                    'purchases',
                    'sale_returns',
                    'purchase_returns',
                    'multi_branch',
                    'banking',
                    'advanced_accounting',
                    'financial_reports',
                    'stock_alerts',
                    'staff_roles',
                    'barcode_scanner',
                    'receipt_printer',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Full-fledged enterprise solution with unlimited power and priority support.',
                'price_monthly' => 4999.00,
                'price_yearly' => 49990.00,
                'max_users' => 9999,
                'max_products' => 999999,
                'max_branches' => 10,
                'features' => [
                    'pos',
                    'inventory',
                    'sales',
                    'customers',
                    'suppliers',
                    'purchases',
                    'sale_returns',
                    'purchase_returns',
                    'multi_branch',
                    'banking',
                    'advanced_accounting',
                    'financial_reports',
                    'stock_alerts',
                    'staff_roles',
                    'api_access',
                    'priority_support',
                    'sms_alerts',
                    'custom_invoice',
                    'barcode_scanner',
                    'receipt_printer',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
