<?php

namespace App\Http\Middleware;

use App\Services\LegacyInventoryCarryForward;
use Closure;
use Illuminate\Http\Request;

class CarryForwardLegacyInventory
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin', 'admin/*') && $request->user()) {
            app(LegacyInventoryCarryForward::class)->ensure();
        }

        return $next($request);
    }
}
