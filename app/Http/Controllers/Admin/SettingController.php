<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\InventoryPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::getAll();

        $defaultSettings = [
            'business_name' => 'My Store',
            'business_phone' => '',
            'business_email' => '',
            'business_address' => '',
            'business_timezone' => 'Asia/Dhaka',
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'tax_percentage' => '0',
            'invoice_prefix' => 'INV-',
            'invoice_footer_note' => 'Thank you for shopping with us! Please come again.',
            'receipt_paper_size' => '80mm', // 80mm, 58mm, A4
        ];

        $mergedSettings = array_merge($defaultSettings, $settings);
        $policy = app(InventoryPolicy::class)->current();
        $mergedSettings['inventory_costing_method'] = $policy->method;

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $mergedSettings,
            'inventoryMethodLocked' => (bool) $policy->locked,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'inventory_costing_method' => 'sometimes|required|in:fifo,moving_weighted_average',
            'business_timezone' => 'sometimes|required|timezone',
        ]);
        if ($request->has('inventory_costing_method')) {
            app(InventoryPolicy::class)->select($request->string('inventory_costing_method')->toString());
        }
        $data = $request->only([
            'business_name', 'business_phone', 'business_email', 'business_address',
            'business_timezone',
            'currency_symbol', 'currency_code', 'tax_percentage', 'invoice_prefix',
            'invoice_footer_note', 'receipt_paper_size',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        // Sync to Central Tenant record if this is running in a tenant context
        if (function_exists('tenant') && tenant()) {
            try {
                $tenant = tenant();
                $tenantUpdate = [];
                if (! empty($data['business_name'])) {
                    $tenantUpdate['name'] = $data['business_name'];
                }
                if (! empty($data['business_email'])) {
                    $tenantUpdate['email'] = $data['business_email'];
                }
                if (isset($data['business_phone'])) {
                    $tenantUpdate['phone'] = $data['business_phone'];
                }
                if (isset($data['business_address'])) {
                    $tenantUpdate['address'] = $data['business_address'];
                }
                if (! empty($tenantUpdate)) {
                    $tenant->update($tenantUpdate);
                }
            } catch (\Throwable $e) {
                Log::warning('Could not sync tenant settings to central database: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
