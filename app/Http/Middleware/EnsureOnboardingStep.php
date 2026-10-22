<?php
// app/Http/Middleware/EnsureOnboardingStep.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingStep
{
    /**
     * Guard the onboarding routes so users go through them in order:
     *
     *   personal → allowed to view/save anytime they haven't finished BOTH
     *   identity → requires Personal Info to exist first
     *
     * Once BOTH are done, both routes redirect to the tenant dashboard.
     */
    public function handle(Request $request, Closure $next, string $step): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $hasPersonal = $user->personalInformation()->exists();
        $hasIdentity = $user->identityVerification()->exists();

        // Fully onboarded → dashboard.
        if ($hasPersonal && $hasIdentity) {
            return redirect()->route('tenants.dashboard');
        }

        if ($step === 'personal') {
            // Always allow viewing/saving Step 1 while onboarding is incomplete.
            return $next($request);
        }

        if ($step === 'identity') {
            // Must complete Step 1 first.
            if (! $hasPersonal) {
                return redirect()
                    ->route('personal-information')
                    ->with('status', 'Please complete your personal information first.');
            }
            return $next($request);
        }

        return $next($request);
    }
}