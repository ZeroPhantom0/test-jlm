<?php
// app/Http/Middleware/EnsureBalanceSettled.php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the tenant Services pages (Maintenance, Room Change, Stay Extension)
 * while the tenant still has a remaining balance in Billing.
 */
class EnsureBalanceSettled
{
    public const MESSAGE = 'Services are unavailable while you have a remaining balance. '
        . 'Please settle your balance in full to access Maintenance, Room Change and Stay Extension.';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && Tenant::hasUnsettledBalance($request->user()->id)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => self::MESSAGE], 403);
            }

            return redirect()->route('tenants.dashboard')->with('error', self::MESSAGE);
        }

        return $next($request);
    }
}
