<?php
// app/Http/Middleware/PreventBackButtonCache.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackButtonCache
{
    /**
     * Prevent the browser from caching authenticated pages so that
     * pressing the Back button after logout forces a fresh request
     * (which the `auth` middleware will reject → redirect to login).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}