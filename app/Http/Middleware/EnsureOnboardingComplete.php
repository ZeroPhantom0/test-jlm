<?php
// app/Http/Middleware/EnsureOnboardingComplete.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * Block tenant portal access until BOTH onboarding steps are done.
     *
     * If a tenant hasn't finished BOTH steps, they always go back to
     * Step 1 (Personal Information) — NOT to whichever step is missing.
     * This guarantees a consistent "start from the beginning" flow on
     * every visit and every fresh login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in → let the `auth` middleware handle it.
        if (! $user) {
            return $next($request);
        }

        // Admins bypass onboarding entirely.
        if ($user->isAdmin()) {
            return $next($request);
        }

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