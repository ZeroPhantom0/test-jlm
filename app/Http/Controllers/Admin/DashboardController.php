<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get selected date (default to today)
        $selectedDateStr = $request->query('date', now()->toDateString());
        $selectedDate = Carbon::parse($selectedDateStr);

        // 2. Total Revenue (Revenue collected exactly on the selected date)
        $revenue = Invoice::where('status', 'Paid')
            ->whereDate('date_paid', $selectedDate)
            ->sum('amount_paid');

        // 3. Active Tenants (Tenants who checked in on or before the selected date)
        $activeTenants = Tenant::where('status', 'active')
            ->whereHas('approvedReservation', function($q) use ($selectedDate) {
                $q->whereDate('check_in', '<=', $selectedDate);
            })
            ->count();

        // 4. Service Requests (Requests created exactly on the selected date)
        $serviceRequests = MaintenanceRequest::whereDate('created_at', $selectedDate)->count();
        
        // 5. Pending Payments (Invoices CREATED on or before the selected date that are still unpaid)
        $pendingPaymentsCount = Invoice::whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->whereDate('created_at', '<=', $selectedDate)
            ->count();
        $pendingPaymentsAmount = Invoice::whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->whereDate('created_at', '<=', $selectedDate)
            ->sum('balance');

        // 6. Occupancy Rate (Rooms occupied as of selected date)
        $totalRooms = Room::whereDate('created_at', '<=', $selectedDate)->count();
        $occupiedRooms = Room::where('status', 'Occupied')
            ->whereDate('created_at', '<=', $selectedDate)
            ->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
        $reservedRooms = Room::where('status', 'Reserved')->whereDate('created_at', '<=', $selectedDate)->count();
        $availableRooms = Room::where('status', 'Available')->whereDate('created_at', '<=', $selectedDate)->count();

        // 7. Reservations (Reservations submitted exactly on the selected date)
        $reservations = Reservation::whereDate('created_at', $selectedDate)->count();

        // 8. Financial Chart Data (Last 6 Months)
        $chartLabels = [];
        $chartIncome = [];
        $chartExpenses = []; 

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chartLabels[] = $month->format('M');
            
            $income = Invoice::where('status', 'Paid')
                ->whereMonth('date_paid', $month->month)
                ->whereYear('date_paid', $month->year)
                ->sum('amount_paid');
            $chartIncome[] = (float) $income;
            
            $chartExpenses[] = 0; 
        }

        // 9. Calendar Math
        $month = $selectedDate->month;
        $year = $selectedDate->year;
        $daysInMonth = $selectedDate->daysInMonth;
        $firstDayOfMonth = Carbon::create($year, $month, 1)->dayOfWeek; 

        return view('admin.dashboard', compact(
            'selectedDate',
            'selectedDateStr',
            'revenue',
            'activeTenants',
            'serviceRequests',
            'pendingPaymentsCount',
            'pendingPaymentsAmount',
            'totalRooms',
            'occupiedRooms',
            'occupancyRate',
            'reservedRooms',
            'availableRooms',
            'reservations',
            'month',
            'year',
            'daysInMonth',
            'firstDayOfMonth',
            'chartLabels',
            'chartIncome',
            'chartExpenses'
        ));
    }
}