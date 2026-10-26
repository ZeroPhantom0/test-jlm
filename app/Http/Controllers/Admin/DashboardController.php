<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $payload = $this->buildPayload($request);

        return view('admin.dashboard', $payload);
    }

    /**
     * Live data endpoint for polling / Echo refresh.
     * GET /admin/dashboard/data
     */
    public function data(Request $request)
    {
        return response()->json($this->buildPayload($request));
    }

    private function buildPayload(Request $request): array
    {
        // 1. Get selected date (default to today)
        $selectedDateStr = $request->query('date', now()->toDateString());
        $selectedDate = Carbon::parse($selectedDateStr);

        // 2. Total Revenue
        $revenue = Invoice::where('status', 'Paid')
            ->whereDate('date_paid', $selectedDate)
            ->sum('amount_paid');

        // 3. Active Tenants
        $activeTenants = Tenant::where('status', 'active')
            ->whereHas('approvedReservation', function ($q) use ($selectedDate) {
                $q->whereDate('check_in', '<=', $selectedDate);
            })
            ->count();

        // 4. Service Requests — TOTAL of ALL 3 services
        $maintenanceCount    = MaintenanceRequest::whereDate('created_at', $selectedDate)->count();
        $roomChangeCount     = RoomChangeRequest::whereDate('created_at', $selectedDate)->count();
        $stayExtensionCount  = StayExtensionRequest::whereDate('created_at', $selectedDate)->count();
        $totalServiceRequests = $maintenanceCount + $roomChangeCount + $stayExtensionCount;

        // 5. Pending Payments
        $pendingPaymentsCount = Invoice::whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->whereDate('created_at', '<=', $selectedDate)
            ->count();
        $pendingPaymentsAmount = Invoice::whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->whereDate('created_at', '<=', $selectedDate)
            ->sum('balance');

        // 6. Occupancy Rate
        $totalRooms    = Room::whereDate('created_at', '<=', $selectedDate)->count();
        $occupiedRooms = Room::where('status', 'Occupied')->whereDate('created_at', '<=', $selectedDate)->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
        $reservedRooms = Room::where('status', 'Reserved')->whereDate('created_at', '<=', $selectedDate)->count();
        $availableRooms = Room::where('status', 'Available')->whereDate('created_at', '<=', $selectedDate)->count();

        // 7. Reservations
        $reservations = Reservation::whereDate('created_at', $selectedDate)->count();

        // 8. Financial Chart Data (Last 6 Months)
        $chartLabels   = [];
        $chartIncome   = [];
        $chartExpenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chartLabels[] = $month->format('M');
            $income = Invoice::where('status', 'Paid')
                ->whereMonth('date_paid', $month->month)
                ->whereYear('date_paid', $month->year)
                ->sum('amount_paid');
            $chartIncome[]   = (float) $income;
            $chartExpenses[] = 0;
        }

        // 9. Calendar Math
        $month           = $selectedDate->month;
        $year            = $selectedDate->year;
        $daysInMonth     = $selectedDate->daysInMonth;
        $firstDayOfMonth = Carbon::create($year, $month, 1)->dayOfWeek;

        // 10. Recent Activity (from audit_logs)
        $recentActivity = AuditLog::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($log) {
                $iconBg = match ($log->status) {
                    'error'   => 'bg-rose-100 text-rose-600',
                    'warning' => 'bg-amber-100 text-amber-600',
                    default   => 'bg-emerald-100 text-emerald-600',
                };
                return [
                    'id'          => $log->id,
                    'action'      => $log->action,
                    'description' => $log->description,
                    'user_name'   => $log->user_name,
                    'icon'        => $log->action_icon,
                    'icon_bg'     => $iconBg,
                    'time'        => $log->created_at ? $log->created_at->diffForHumans() : 'Just now',
                ];
            });

        return [
            'selectedDate'          => $selectedDate,
            'selectedDateStr'       => $selectedDate->toDateString(),
            'revenue'               => (float) $revenue,
            'activeTenants'         => $activeTenants,
            'serviceRequests'       => $totalServiceRequests,
            'serviceBreakdown'      => [
                'maintenance'     => $maintenanceCount,
                'room_change'     => $roomChangeCount,
                'stay_extension'  => $stayExtensionCount,
            ],
            'pendingPaymentsCount'  => $pendingPaymentsCount,
            'pendingPaymentsAmount' => (float) $pendingPaymentsAmount,
            'totalRooms'            => $totalRooms,
            'occupiedRooms'         => $occupiedRooms,
            'occupancyRate'         => $occupancyRate,
            'reservedRooms'         => $reservedRooms,
            'availableRooms'        => $availableRooms,
            'reservations'          => $reservations,
            'month'                 => $month,
            'year'                  => $year,
            'daysInMonth'           => $daysInMonth,
            'firstDayOfMonth'       => $firstDayOfMonth,
            'chartLabels'           => $chartLabels,
            'chartIncome'           => $chartIncome,
            'chartExpenses'         => $chartExpenses,
            'recentActivity'        => $recentActivity,
        ];
    }
}