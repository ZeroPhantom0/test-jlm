<?php
// app/Http/Controllers/Admin/AnnouncementController.php

namespace App\Http\Controllers\Admin;

use App\Events\AnnouncementUpdated;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.announcements', $this->buildPayload($request));
    }

    /**
     * Live data endpoint for polling / Echo refresh.
     * GET /admin/announcements/data
     */
    public function data(Request $request)
    {
        return response()->json($this->buildPayload($request));
    }

    private function buildPayload(Request $request): array
    {
        $filters = $request->only(['search', 'priority']);

        $announcements = Announcement::with('author')
            ->filter($filters)
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        $metrics = [
            'total'     => Announcement::count(),
            'pinned'    => Announcement::pinned()->count(),
            'important' => Announcement::ofPriority('Important')->count(),
            'urgent'    => Announcement::ofPriority('Urgent')->count(),
        ];

        $announcementsForReact = $announcements->mapWithKeys(function ($a) {
            return [$a->id => $a->toReactArray()];
        })->toArray();

        // Structured rows for JS re-rendering
        $rows = $announcements->map(fn (Announcement $a) => [
            'id'             => $a->id,
            'title'          => $a->title,
            'content'        => $a->content,
            'priority'       => $a->priority,
            'priority_class' => $a->priority_class,
            'priority_icon'  => $a->priority_icon,
            'icon_bg'        => $a->icon_bg,
            'box_class'      => $a->box_class,
            'is_pinned'      => (bool) $a->is_pinned,
            'author'         => $a->author->name ?? 'Unknown',
            'created_at'     => $a->created_at?->format('M d, Y'),
        ])->values();

        return compact(
            'announcements',
            'metrics',
            'filters',
            'announcementsForReact',
            'rows'
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'content'      => ['required', 'string'],
            'priority'     => ['nullable', Rule::in(['Normal', 'Important', 'Urgent'])],
            'is_pinned'    => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'priority'     => $validated['priority'] ?? 'Normal',
            'is_pinned'    => $validated['is_pinned'] ?? false,
            'is_published' => $validated['is_published'] ?? true,
            'created_by'   => $request->user()->id,
            'published_at' => now(),
        ]);

        event(new AnnouncementUpdated('created', $announcement->id));

        return response()->json([
            'success' => true,
            'message' => 'Announcement posted successfully.',
            'data'    => $announcement->load('author')->toReactArray(),
        ]);
    }

    public function show(Announcement $announcement)
    {
        return response()->json([
            'success' => true,
            'data'    => $announcement->load('author')->toReactArray(),
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'content'      => ['sometimes', 'string'],
            'priority'     => ['sometimes', Rule::in(['Normal', 'Important', 'Urgent'])],
            'is_pinned'    => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $announcement->update($validated);

        event(new AnnouncementUpdated('updated', $announcement->id));

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully.',
            'data'    => $announcement->fresh()->load('author')->toReactArray(),
        ]);
    }

    public function togglePin(Announcement $announcement)
    {
        $announcement->update([
            'is_pinned' => ! $announcement->is_pinned,
        ]);

        event(new AnnouncementUpdated('pinned', $announcement->id));

        return response()->json([
            'success' => true,
            'message' => $announcement->is_pinned
                ? 'Announcement pinned.'
                : 'Announcement unpinned.',
            'data'    => $announcement->fresh()->load('author')->toReactArray(),
        ]);
    }

    public function destroy(Announcement $announcement)
    {
        $id = $announcement->id;
        $announcement->delete();

        event(new AnnouncementUpdated('deleted', $id));

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.',
        ]);
    }
}