<?php
// app/Http/Middleware/EnsureOnboardingComplete.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in → let the `auth` middleware handle it.
        if (! $user) {
            return $next($request);
        }

        // ── TENANT GUARD ──────────────────────────────────────────
        // This middleware is only applied to /tenants/*. Any non-tenant
        // user (admin, future staff, etc.) must NOT be allowed through.
        if (! $user->isTenant()) {
            abort(403, 'Unauthorized. Tenant access only.');
        }
        // ──────────────────────────────────────────────────────────

        $hasPersonal = $user->personalInformation()->exists();
        $hasIdentity = $user->identityVerification()->exists();

        // Both done → allow through.
        if ($hasPersonal && $hasIdentity) {
            return $next($request);
        }

        // Not fully onboarded → always start at Step 1.
        return redirect()
            ->route('personal-information')
            ->with('status', 'Please complete your onboarding to continue.');
    }
}