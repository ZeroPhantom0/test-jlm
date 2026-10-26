<?php
// app/Http/Controllers/Tenant/TenantAnnouncementController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class TenantAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        return view('tenants.announcements', $this->buildPayload($request));
    }

    /**
     * Live data endpoint for polling / Echo refresh.
     * GET /tenants/announcements/data
     */
    public function data(Request $request)
    {
        return response()->json($this->buildPayload($request));
    }

    private function buildPayload(Request $request): array
    {
        $filters = $request->only(['search', 'priority']);

        $announcements = Announcement::with('author')
            ->published()
            ->filter($filters)
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        $pinnedAnnouncements  = $announcements->where('is_pinned', true)->values();
        $regularAnnouncements = $announcements->where('is_pinned', false)->values();

        $metrics = [
            'total'     => $announcements->count(),
            'pinned'    => $pinnedAnnouncements->count(),
            'important' => $announcements->where('priority', 'Important')->count(),
            'urgent'    => $announcements->where('priority', 'Urgent')->count(),
        ];

        // Structured rows the JS renderer uses
        $rows = $announcements->map(fn (Announcement $a) => [
            'id'             => $a->id,
            'title'          => $a->title,
            'content'        => $a->content,
            'priority'       => $a->priority,
            'priority_lc'    => strtolower($a->priority ?? 'normal'),
            'is_pinned'      => (bool) $a->is_pinned,
            'author'         => $a->author->name ?? 'Administrator',
            'created_at'     => $a->created_at?->format('M d, Y'),
        ])->values();

        return compact(
            'announcements',
            'pinnedAnnouncements',
            'regularAnnouncements',
            'metrics',
            'filters',
            'rows'
        );
    }
}