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

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
