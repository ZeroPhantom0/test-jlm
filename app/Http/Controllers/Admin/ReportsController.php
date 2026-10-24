<?php
// app/Http/Controllers/Admin/ReportsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ReportsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(
        protected ReportsService $reports
    ) {}

    public function index(Request $request)
    {
        $range = $request->query('range', 'monthly');

        $summary = $this->reports->summaryMetrics($range);

        $occupancyTrend     = $this->reports->occupancyTrend($range);
        $occupancyByType    = $this->reports->occupancyByRoomType();

        $revenueBreakdown   = $this->reports->revenueBreakdown($range);

        $serviceByCategory  = $this->reports->serviceRequestsByCategory($range);

        $serviceMax = 1;
        foreach ($serviceByCategory as $m) {
            foreach (['plumbing', 'electrical', 'hvac', 'general'] as $k) {
                $serviceMax = max($serviceMax, (int) $m[$k]);
            }
        }

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

    /**
     * Stream a CSV export and record an audit log entry so the
     * admin notification bell can surface recent exports.
     */
    public function export(Request $request): StreamedResponse
    {
        $range   = $request->query('range', 'monthly');
        $section = $request->query('section', 'occupancy');

        $rows = [];

        if ($section === 'revenue' || $section === 'all') {
            $rows[] = ['Section', 'Period', 'Rent', 'Utilities', 'Other', 'Total'];
            foreach ($this->reports->revenueBreakdown($range) as $r) {
                $rows[] = [
                    'Revenue', $r['label'], $r['rent'], $r['util'], $r['other'], $r['total'],
                ];
            }
        }

        if ($section === 'occupancy' || $section === 'all') {
            $rows[] = ['Section', 'Room Type', 'Occupied', 'Reserved', 'Vacant', 'Total'];
            foreach ($this->reports->occupancyByRoomType() as $r) {
                $rows[] = [
                    'Occupancy', $r['type'], $r['occupied'], $r['reserved'], $r['vacant'], $r['total'],
                ];
            }
        }

        if ($section === 'service' || $section === 'all') {
            $rows[] = ['Section', 'Period', 'Plumbing', 'Electrical', 'HVAC', 'General'];
            foreach ($this->reports->serviceRequestsByCategory($range) as $r) {
                $rows[] = [
                    'Service', $r['label'], $r['plumbing'], $r['electrical'], $r['hvac'], $r['general'],
                ];
            }
        }

        if ($section === 'payments' || $section === 'all') {
            $rows[] = ['Section', 'Status', 'Count', 'Amount'];
            foreach ($this->reports->paymentStatusSummary() as $status => $data) {
                $rows[] = ['Payments', $status, $data['count'], $data['amount']];
            }
        }

        $filename = 'reports-' . $section . '-' . now()->format('Y-m-d_His') . '.csv';

        AuditLogger::log([
            'action'      => 'EXPORT',
            'module'      => 'REPORTS',
            'description' => 'Exported ' . $section . ' report (range: ' . $range . ')',
            'status'      => 'success',
        ]);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}