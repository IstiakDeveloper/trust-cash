<?php

namespace App\Http\Middleware;

use App\Models\PendingSale;
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
                    if (class_exists(\App\Models\Setting::class)) {
                        $tenantLogo = \App\Models\Setting::get('business_logo', '');
                        if (!empty($tenantLogo)) {
                            return $tenantLogo;
                        }
                        return \App\Models\Setting::getCentral('site_logo', '');
                    }
                } catch (\Throwable $e) {}
                return '';
            },
            'currency_symbol' => function () {
                try {
                    if (class_exists(\App\Models\Setting::class) && method_exists(\App\Models\Setting::class, 'get')) {
                        return \App\Models\Setting::get('currency_symbol', '৳');
                    }
                } catch (\Throwable $e) {}
                return '৳';
            },
            'platform' => function () {
                try {
                    if (class_exists(\App\Models\Setting::class)) {
                        return [
                            'name'          => \App\Models\Setting::getCentral('app_name', 'TrustCash'),
                            'tagline'       => \App\Models\Setting::getCentral('app_tagline', 'Cloud POS & Accounting'),
                            'logo'          => \App\Models\Setting::getCentral('site_logo', ''),
                            'favicon'       => \App\Models\Setting::getCentral('site_favicon', ''),
                            'phone'         => \App\Models\Setting::getCentral('support_phone', '+880 1700-000000'),
                            'email'         => \App\Models\Setting::getCentral('support_email', 'support@trustcash.com'),
                            'address'       => \App\Models\Setting::getCentral('company_address', 'Dhaka, Bangladesh'),
                            'whatsapp'      => \App\Models\Setting::getCentral('whatsapp_number', ''),
                            'facebook'      => \App\Models\Setting::getCentral('social_facebook', ''),
                            'youtube'       => \App\Models\Setting::getCentral('social_youtube', ''),
                            'currency'      => \App\Models\Setting::getCentral('currency_symbol', '৳'),
                            'nav_features'  => in_array(\App\Models\Setting::getCentral('nav_show_features', 'true'), ['true', '1', true, 1], true),
                            'nav_use_cases' => in_array(\App\Models\Setting::getCentral('nav_show_use_cases', 'true'), ['true', '1', true, 1], true),
                            'nav_pricing'   => in_array(\App\Models\Setting::getCentral('nav_show_pricing', 'true'), ['true', '1', true, 1], true),
                            'nav_faq'       => in_array(\App\Models\Setting::getCentral('nav_show_faq', 'true'), ['true', '1', true, 1], true),
                            'nav_cta_text'  => \App\Models\Setting::getCentral('nav_cta_text', '১৪ দিন ফ্রি ট্রায়াল শুরু করুন'),
                            'nav_cta_url'   => \App\Models\Setting::getCentral('nav_cta_url', '/register-business'),
                        ];
                    }
                } catch (\Throwable $e) {}
                return null;
            },
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'sale'    => fn() => $request->session()->get('sale'),
            ],
        ];
    }

}
