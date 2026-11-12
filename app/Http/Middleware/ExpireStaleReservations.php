<?php
// app/Http/Middleware/ExpireStaleReservations.php

namespace App\Http\Middleware;

use App\Services\ReservationExpiryService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safety net for when the scheduler isn't running: at most once a minute,
 * a normal page request expires stays whose check-out has passed, so a
 * tenant never sees an ended stay as their "Current Room".
 */
class ExpireStaleReservations
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && Cache::add('reservations:expiry-check', 1, 60)) {
            try {
                app(ReservationExpiryService::class)->run();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
