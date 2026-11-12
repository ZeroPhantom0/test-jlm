<?php
// app/Http/Middleware/EnforceSessionTimeout.php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin idle-session timeout (Settings → Security → Session Timeout).
 *
 * "Activity" = page loads, form submits and any POST/PUT/DELETE (approve,
 * save, …) plus the heartbeat the page sends while the admin is moving the
 * mouse / typing. Background polling (GET XHR such as the notification feed
 * or live table refreshes) does NOT count, so a page left open is still
 * logged out after the chosen idle time.
 */
class EnforceSessionTimeout
{
    public const SESSION_KEY = 'admin_last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return $next($request);
        }

        $timeoutMinutes = (int) ($user->session_timeout ?: 120);
        $last = (int) $request->session()->get(self::SESSION_KEY, 0);
        $now  = now()->timestamp;

        if ($last > 0 && ($now - $last) > $timeoutMinutes * 60) {
            try {
                AuditLogger::log([
                    'action'      => 'LOGOUT',
                    'module'      => 'AUTHENTICATION',
                    'description' => "Session timed out after {$timeoutMinutes} minutes of inactivity.",
                    'status'      => 'warning',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your session expired after ' . $this->label($timeoutMinutes) . ' of inactivity. Please log in again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message'  => $message,
                    'redirect' => route('admin.login'),
                ], 401);
            }

            return redirect()->route('admin.login')->with('error', $message);
        }

        $isPassivePoll = $request->isMethod('GET') && ($request->ajax() || $request->expectsJson());

        if (! $isPassivePoll || $last === 0) {
            $request->session()->put(self::SESSION_KEY, $now);
        }

        return $next($request);
    }

    private function label(int $minutes): string
    {
        return $minutes % 60 === 0
            ? ($minutes / 60) . ' hour' . ($minutes === 60 ? '' : 's')
            : $minutes . ' minutes';
    }
}
