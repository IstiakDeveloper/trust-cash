<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\StorefrontController;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            return app(StorefrontController::class)->index($request);
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Central/Landing', [
            'plans' => $plans,
        ]);
    }
}
