<?php
// app/Http/Middleware/EnsureOnboardingStep.php

namespace App\Http\Middleware;

use App\Services\PendingSignup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingStep
{
    /**
     * Guard the onboarding routes so they're visited in order:
     *
     *   personal → always allowed while onboarding is unfinished
     *   identity → requires the personal information step first
     *
     * Who may be here:
     *   • a pending sign-up (NO account yet — see PendingSignup), or
     *   • a logged-in user created before that flow who never finished.
     * Anyone else is sent to sign up / log in; finished users go to the dashboard.
     */
    public function handle(Request $request, Closure $next, string $step): Response
    {
        // ── Pending sign-up (guest with a session draft) ──
        if ($draft = PendingSignup::get()) {
            if ($step === 'identity' && empty($draft['personal'])) {
                return redirect()
                    ->route('personal-information')
                    ->with('status', 'Please complete your personal information first.');
            }
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()
                ->route('signup')
                ->with('status', 'Your sign-up session expired. Please start again — no account was created.');
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

        if ($step === 'identity' && ! $hasPersonal) {
            return redirect()
                ->route('personal-information')
                ->with('status', 'Please complete your personal information first.');
        }

        return $next($request);
    }
}
