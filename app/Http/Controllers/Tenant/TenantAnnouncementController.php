<?php
// app/Http/Controllers/Tenant/TenantAnnouncementController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class TenantAnnouncementController extends Controller
{
    /**
     * Display the tenant's announcements page.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'priority']);

        $announcements = Announcement::with('author')
            ->published()
            ->filter($filters)
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        // Split pinned vs regular for the UI
        $pinnedAnnouncements  = $announcements->where('is_pinned', true)->values();
        $regularAnnouncements = $announcements->where('is_pinned', false)->values();

        // Metrics
        $metrics = [
            'total'     => $announcements->count(),
            'pinned'    => $pinnedAnnouncements->count(),
            'important' => $announcements->where('priority', 'Important')->count(),
            'urgent'    => $announcements->where('priority', 'Urgent')->count(),
        ];

        return view('tenants.announcements', compact(
            'announcements',
            'pinnedAnnouncements',
            'regularAnnouncements',
            'metrics',
            'filters'
        ));
    }
}