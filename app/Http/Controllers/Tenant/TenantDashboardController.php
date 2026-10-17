<?php
// app/Http/Controllers/Tenant/TenantDashboardController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TenantDashboardController extends Controller
{
    /**
     * Display the tenant's dashboard with live data.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // -------------------------------------------------------
        // Tenant profile + current room
        // -------------------------------------------------------
        $tenant = Tenant::with(['room'])
            ->where('user_id', $user->id)
            ->first();

        $room = $tenant?->room;

        // -------------------------------------------------------
        // Outstanding balance (unpaid / partial / overdue invoices)
        // -------------------------------------------------------
        $unpaidInvoices = Invoice::where('tenant_id', $tenant?->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->orderBy('due_date')
            ->get();

        $outstandingBalance = (float) $unpaidInvoices->sum('balance');
        $unpaidCount        = $unpaidInvoices->count();

        // -------------------------------------------------------
        // Active maintenance requests (Pending + In Progress)
        // -------------------------------------------------------
        $activeRequests = MaintenanceRequest::where('tenant_id', $tenant?->id)
            ->whereIn('status', ['Pending', 'In Progress'])
            ->latest()
            ->get();

        // -------------------------------------------------------
        // Latest announcements (pinned first)
        // -------------------------------------------------------
        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(5)
            ->get();

        // -------------------------------------------------------
        // Mini calendar for the current month — PH time
        // -------------------------------------------------------
        $today = Carbon::now('Asia/Manila');

        $year         = $today->year;
        $month        = $today->month;
        $monthLabel   = $today->format('F Y');       // e.g. "October 2026"
        $todayDay     = $today->day;                 // e.g. 18
        $daysInMonth  = $today->copy()->endOfMonth()->day;
        // 0 = Sunday, 6 = Saturday
        $startWeekday = (int) $today->copy()->startOfMonth()->format('w');

        // -------------------------------------------------------
        // Greeting name (first word of full name)
        // -------------------------------------------------------
        $greetingName = explode(' ', trim($user->name))[0] ?: 'Tenant';

        return view('tenants.dashboard', compact(
            'user',
            'tenant',
            'room',
            'unpaidInvoices',
            'outstandingBalance',
            'unpaidCount',
            'activeRequests',
            'announcements',
            'year',
            'month',
            'monthLabel',
            'todayDay',
            'daysInMonth',
            'startWeekday',
            'greetingName'
        ));
    }
}