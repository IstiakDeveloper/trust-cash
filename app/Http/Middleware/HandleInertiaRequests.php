<?php

namespace App\Http\Middleware;

use App\Models\PendingSale;
use App\Models\Setting;
use App\Support\TenancyHost;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'business_date' => now()->toDateString(),
            'auth' => [
                'user' => $request->user() ? $request->user()->loadMissing('role') : null,
            ],
            'counts' => [
                'pendingOrders' => fn () => $request->user()
                    ? PendingSale::query()->where('status', 'pending')->count()
                    : 0,
            ],
            'app_name' => config('app.name', 'TrustCash'),
            'business_name' => fn () => business_name(),
            'business_phone' => fn () => business_phone(),
            'business_email' => fn () => business_email(),
            'business_address' => fn () => business_address(),
            'business_details' => fn () => business_details(),
            'business_logo' => function () {
                try {
                    if (class_exists(Setting::class)) {
                        $tenantLogo = Setting::get('business_logo', '');
                        if (! empty($tenantLogo)) {
                            return $tenantLogo;
                        }

                        return Setting::getCentral('site_logo', '');
                    }
                } catch (\Throwable $e) {
                }

                return '';
            },
            'currency_symbol' => function () {
                try {
                    if (class_exists(Setting::class) && method_exists(Setting::class, 'get')) {
                        return Setting::get('currency_symbol', '৳');
                    }
                } catch (\Throwable $e) {
                }

                return '৳';
            },
            'platform' => function () {
                try {
                    if (class_exists(Setting::class)) {
                        return [
                            'name' => Setting::getCentral('app_name', 'TrustCash'),
                            'tagline' => Setting::getCentral('app_tagline', 'Cloud POS & Accounting'),
                            'logo' => Setting::getCentral('site_logo', ''),
                            'favicon' => Setting::getCentral('site_favicon', ''),
                            'phone' => Setting::getCentral('support_phone', '+880 1700-000000'),
                            'email' => Setting::getCentral('support_email', 'support@trustcash.com'),
                            'address' => Setting::getCentral('company_address', 'Dhaka, Bangladesh'),
                            'whatsapp' => Setting::getCentral('whatsapp_number', ''),
                            'facebook' => Setting::getCentral('social_facebook', ''),
                            'youtube' => Setting::getCentral('social_youtube', ''),
                            'currency' => Setting::getCentral('currency_symbol', '৳'),
                            'nav_features' => in_array(Setting::getCentral('nav_show_features', 'true'), ['true', '1', true, 1], true),
                            'nav_use_cases' => in_array(Setting::getCentral('nav_show_use_cases', 'true'), ['true', '1', true, 1], true),
                            'nav_pricing' => in_array(Setting::getCentral('nav_show_pricing', 'true'), ['true', '1', true, 1], true),
                            'nav_faq' => in_array(Setting::getCentral('nav_show_faq', 'true'), ['true', '1', true, 1], true),
                            'nav_cta_text' => Setting::getCentral('nav_cta_text', '১৪ দিন ফ্রি ট্রায়াল শুরু করুন'),
                            'nav_cta_url' => Setting::getCentral('nav_cta_url', '/register-business'),
                        ];
                    }
                } catch (\Throwable $e) {
                }

                return null;
            },
            'impersonation' => fn () => $request->session()->get('impersonator_id') ? true : null,
            'shop_url' => function () {
                if (! function_exists('tenancy') || ! tenancy()->initialized || ! tenant()) {
                    return null;
                }

                return TenancyHost::shopUrl((string) tenant('id'));
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'sale' => fn () => $request->session()->get('sale'),
            ],
        ];
    }
}
