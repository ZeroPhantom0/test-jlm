<?php
// app/Http/Controllers/Admin/AnnouncementController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    /**
     * Display the announcements list.
     */
    public function index(Request $request)
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

        // Pass data for React modals
        $announcementsForReact = $announcements->mapWithKeys(function ($a) {
            return [$a->id => $a->toReactArray()];
        })->toArray();

        return view('admin.announcements', compact('announcements', 'metrics', 'filters', 'announcementsForReact'));
    }

    /**
     * Store a newly created announcement.
     */
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

        return response()->json([
            'success' => true,
            'message' => 'Announcement posted successfully.',
            'data'    => $announcement->load('author')->toReactArray(),
        ]);
    }

    /**
     * Display the specified announcement.
     */
    public function show(Announcement $announcement)
    {
        return response()->json([
            'success' => true,
            'data'    => $announcement->load('author')->toReactArray(),
        ]);
    }

    /**
     * Update the specified announcement.
     */
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

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully.',
            'data'    => $announcement->fresh()->load('author')->toReactArray(),
        ]);
    }

    /**
     * Toggle the pinned state of an announcement.
     */
    public function togglePin(Announcement $announcement)
    {
        $announcement->update([
            'is_pinned' => ! $announcement->is_pinned,
        ]);

        return response()->json([
            'success' => true,
            'message' => $announcement->is_pinned
                ? 'Announcement pinned.'
                : 'Announcement unpinned.',
            'data'    => $announcement->fresh()->load('author')->toReactArray(),
        ]);
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.',
        ]);
    }
}