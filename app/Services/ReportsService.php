<?php
// app/Services/ReportsService.php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsService
{
    /**
     * Get the date range start and end based on the filter.
     */
    public function getDateRange(string $range): array
    {
        $end = now();

        switch ($range) {
            case 'daily':
                $start = now()->startOfDay();
                $labelFormat = 'h:i A';
                $limit = 24;
                break;
            case 'weekly':
                $start = now()->startOfWeek();
                $labelFormat = 'D';
                $limit = 7;
                break;
            case 'monthly':
                $start = now()->startOfMonth();
                $labelFormat = 'W';
                $limit = 5;
                break;
            case 'yearly':
            default:
                $start = now()->startOfYear();
                $labelFormat = 'M';
                $limit = 12;
                break;
        }

        return [$start, $end, $labelFormat, $limit];
    }

    /**
     * Generate time buckets for charts based on range.
     */
    public function generateBuckets(string $range): array
    {
        [$start, $end, $format, $limit] = $this->getDateRange($range);
        $buckets = [];

        if ($range === 'daily') {
            for ($i = 0; $i < 24; $i++) {
                $buckets[] = [
                    'start' => now()->startOfDay()->addHours($i),
                    'end' => now()->startOfDay()->addHours($i)->endOfHour(),
                    'label' => now()->startOfDay()->addHours($i)->format('h A'),
                ];
            }
        } elseif ($range === 'weekly') {
            for ($i = 0; $i < 7; $i++) {
                $day = now()->startOfWeek()->addDays($i);
                $buckets[] = [
                    'start' => $day->copy()->startOfDay(),
                    'end' => $day->copy()->endOfDay(),
                    'label' => $day->format('D'),
                ];
            }
        } elseif ($range === 'monthly') {
            $weeks = now()->startOfMonth()->diffInWeeks(now()->endOfMonth()) + 1;
            for ($i = 0; $i < $weeks; $i++) {
                $weekStart = now()->startOfMonth()->addWeeks($i)->startOfWeek();
                $buckets[] = [
                    'start' => $weekStart->copy()->startOfDay(),
                    'end' => $weekStart->copy()->endOfWeek(),
                    'label' => 'Week ' . ($i + 1),
                ];
            }
        } else {
            for ($i = 0; $i < 12; $i++) {
                $month = now()->startOfYear()->addMonths($i);
                $buckets[] = [
                    'start' => $month->copy()->startOfMonth(),
                    'end' => $month->copy()->endOfMonth(),
                    'label' => $month->format('M'),
                ];
            }
        }

        return $buckets;
    }

    /**
     * Top summary cards (4 values).
     */
    public function summaryMetrics(string $range): array
    {
        [$start, $end] = $this->getDateRange($range);

        // Revenue this period
        $revenueThisPeriod = (float) Invoice::where('status', 'Paid')
            ->whereBetween('date_paid', [$start, $end])
            ->sum('amount_paid');

        // Revenue previous period (for comparison)
        $prevStart = $start->copy()->subDays($start->diffInDays($end) + 1);
        $prevEnd = $start->copy()->subDay();

        $revenueLastPeriod = (float) Invoice::where('status', 'Paid')
            ->whereBetween('date_paid', [$prevStart, $prevEnd])
            ->sum('amount_paid');

        $revenueChange = $revenueLastPeriod > 0
            ? round((($revenueThisPeriod - $revenueLastPeriod) / $revenueLastPeriod) * 100, 1)
            : 0;

        // Occupancy (current snapshot)
        $totalRooms     = Room::count();
        $occupiedRooms  = Room::where('status', 'Occupied')->count();
        $occupancyRate  = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        // Active tenants (current snapshot)
        $activeTenants = Tenant::where('status', 'active')->count();

        // Open (pending) maintenance requests (current snapshot)
        $openRequests = MaintenanceRequest::where('status', 'Pending')->count();

        return [
            'revenue'         => $revenueThisPeriod,
            'revenue_change'  => $revenueChange,
            'occupancy_rate'  => $occupancyRate,
            'occupied_rooms'  => $occupiedRooms,
            'total_rooms'     => $totalRooms,
            'active_tenants'  => $activeTenants,
            'open_requests'   => $openRequests,
        ];
    }

    /**
     * Occupancy trend chart (historical data).
     */
    public function occupancyTrend(string $range): array
    {
        $buckets = $this->generateBuckets($range);
        $totalRooms = max(Room::count(), 1);
        $out = [];

        foreach ($buckets as $bucket) {
            // Calculate actual state at the END of the bucket
            $activeReservations = Reservation::where('status', 'Approved')
                ->where('check_in', '<=', $bucket['end'])
                ->where(function ($q) use ($bucket) {
                    $q->whereNull('check_out')
                      ->orWhere('check_out', '>=', $bucket['start']);
                })
                ->count();

            $reservedOnly = Reservation::where('status', 'Pending')
                ->where('check_in', '<=', $bucket['end'])
                ->where(function ($q) use ($bucket) {
                    $q->whereNull('check_out')
                      ->orWhere('check_out', '>=', $bucket['start']);
                })
                ->count();

            $occupiedPct = round(($activeReservations / $totalRooms) * 100);
            $reservedPct = round(($reservedOnly / $totalRooms) * 100);
            $vacantPct   = max(0, 100 - $occupiedPct - $reservedPct);

            $out[] = [
                'label'    => $bucket['label'],
                'occupied' => $occupiedPct,
                'reserved' => $reservedPct,
                'vacant'   => $vacantPct,
            ];
        }

        return $out;
    }

    /**
     * Occupancy broken down by room type.
     */
    public function occupancyByRoomType(): array
    {
        $types = Room::select('room_type')
            ->selectRaw("
                SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'Reserved' THEN 1 ELSE 0 END) as reserved,
                SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as vacant,
                COUNT(*) as total
            ")
            ->groupBy('room_type')
            ->get();

        return $types->map(function ($row) {
            return [
                'type'     => $row->room_type,
                'occupied' => (int) $row->occupied,
                'reserved' => (int) $row->reserved,
                'vacant'   => (int) $row->vacant,
                'total'    => (int) $row->total,
            ];
        })->toArray();
    }

    /**
     * Revenue breakdown chart (historical data).
     */
    public function revenueBreakdown(string $range): array
    {
        $buckets = $this->generateBuckets($range);
        $rows = [];

        foreach ($buckets as $bucket) {
            $invoices = Invoice::where('status', 'Paid')
                ->whereBetween('date_paid', [$bucket['start'], $bucket['end']])
                ->get();

            $rent = 0.0;
            $util = 0.0;
            $other = 0.0;
            
            foreach ($invoices as $inv) {
                $desc = strtolower($inv->description ?? '');
                if (str_contains($desc, 'util') || str_contains($desc, 'electric') || str_contains($desc, 'water')) {
                    $util += (float) $inv->amount_paid;
                } elseif (str_contains($desc, 'other') || str_contains($desc, 'fee')) {
                    $other += (float) $inv->amount_paid;
                } else {
                    $rent += (float) $inv->amount_paid;
                }
            }

            $rows[] = [
                'label'  => $bucket['label'],
                'rent'   => $rent,
                'util'   => $util,
                'other'  => $other,
                'total'  => $rent + $util + $other,
            ];
        }

        return $rows;
    }

    /**
     * Service Requests grouped by category (historical data).
     */
    public function serviceRequestsByCategory(string $range): array
    {
        $buckets = $this->generateBuckets($range);
        $rows = [];

        foreach ($buckets as $bucket) {
            $requests = MaintenanceRequest::where('status', '!=', 'Cancelled')
                ->whereBetween('created_at', [$bucket['start'], $bucket['end']])
                ->get();

            $rows[] = [
                'label'      => $bucket['label'],
                'plumbing'   => $requests->filter(fn($r) => stripos($r->category ?? '', 'plumb') !== false)->count(),
                'electrical' => $requests->filter(fn($r) => stripos($r->category ?? '', 'elect') !== false)->count(),
                'hvac'       => $requests->filter(fn($r) => stripos($r->category ?? '', 'hvac') !== false || stripos($r->category ?? '', 'aircon') !== false)->count(),
                'general'    => $requests->filter(function ($r) {
                    $cat = strtolower($r->category ?? '');
                    return !str_contains($cat, 'plumb') && !str_contains($cat, 'elect') && !str_contains($cat, 'hvac') && !str_contains($cat, 'aircon');
                })->count(),
            ];
        }

        return $rows;
    }

    /**
     * Payment methods distribution.
     */
    public function paymentMethodDistribution(string $range): array
    {
        [$start, $end] = $this->getDateRange($range);

        $rows = Invoice::where('status', 'Paid')
            ->whereBetween('date_paid', [$start, $end])
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method')
            ->toArray();

        $total = array_sum($rows);
        if ($total === 0) {
            return [
                'gcash' => 0,
                'bank'  => 0,
                'otc'   => 0,
                'total' => 0,
            ];
        }

        $gcash = (int) round((($rows['GCash'] ?? 0) / $total) * 100);
        $bank  = (int) round((($rows['Bank Transfer'] ?? 0) / $total) * 100);
        $otc   = max(0, 100 - $gcash - $bank);

        return [
            'gcash' => $gcash,
            'bank'  => $bank,
            'otc'   => $otc,
            'total' => $total,
        ];
    }

    /**
     * Payment status summary.
     */
    public function paymentStatusSummary(): array
    {
        $statuses = ['Paid', 'Unpaid', 'Overdue', 'Pending'];
        $out = [];

        foreach ($statuses as $status) {
            $q = Invoice::where('status', $status);
            $out[$status] = [
                'count'  => $q->count(),
                'amount' => (float) $q->sum('balance') ?: (float) $q->sum('total_amount'),
            ];
        }

        return $out;
    }
}