<?php
// app/Http/Controllers/Admin/ReportsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __construct(
        protected ReportsService $reports
    ) {}

    /**
     * Display the reports & analytics dashboard.
     */
    public function index(Request $request)
    {
        // Get the selected range (default: monthly)
        $range = $request->query('range', 'monthly');

        // Summary cards
        $summary = $this->reports->summaryMetrics($range);

        // Occupancy tab
        $occupancyTrend     = $this->reports->occupancyTrend($range);
        $occupancyByType    = $this->reports->occupancyByRoomType();

        // Revenue tab
        $revenueBreakdown   = $this->reports->revenueBreakdown($range);

        // Service requests tab
        $serviceByCategory  = $this->reports->serviceRequestsByCategory($range);
        
        // Max y-axis = highest single-category count across all months (min 1)
        $serviceMax = 1;
        foreach ($serviceByCategory as $m) {
            foreach (['plumbing', 'electrical', 'hvac', 'general'] as $k) {
                $serviceMax = max($serviceMax, (int) $m[$k]);
            }
        }

        // Payments tab
        $paymentMethods     = $this->reports->paymentMethodDistribution($range);
        $paymentSummary     = $this->reports->paymentStatusSummary();

        return view('admin.reports', compact(
            'summary',
            'occupancyTrend',
            'occupancyByType',
            'revenueBreakdown',
            'serviceByCategory',
            'serviceMax',
            'paymentMethods',
            'paymentSummary',
            'range'
        ));
    }
}