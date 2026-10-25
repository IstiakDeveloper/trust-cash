<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
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
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'tax_percentage' => '0',
            'invoice_prefix' => 'INV-',
            'invoice_footer_note' => 'Thank you for shopping with us! Please come again.',
            'receipt_paper_size' => '80mm', // 80mm, 58mm, A4
        ];

        $mergedSettings = array_merge($defaultSettings, $settings);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $mergedSettings,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        // Sync to Central Tenant record if this is running in a tenant context
        if (function_exists('tenant') && tenant()) {
            try {
                $tenant = tenant();
                $tenantUpdate = [];
                if (!empty($data['business_name'])) {
                    $tenantUpdate['name'] = $data['business_name'];
                }
                if (!empty($data['business_email'])) {
                    $tenantUpdate['email'] = $data['business_email'];
                }
                if (isset($data['business_phone'])) {
                    $tenantUpdate['phone'] = $data['business_phone'];
                }
                if (isset($data['business_address'])) {
                    $tenantUpdate['address'] = $data['business_address'];
                }
                if (!empty($tenantUpdate)) {
                    $tenant->update($tenantUpdate);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Could not sync tenant settings to central database: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
