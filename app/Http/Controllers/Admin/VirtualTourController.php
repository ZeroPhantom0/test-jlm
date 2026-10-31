<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\VirtualTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VirtualTourController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $tours = VirtualTour::with('room:id,room_number,total_beds,daily_rent')
            ->withCount('scenes')
            ->filter($filters)
            ->latest()
            ->get();

        $rooms = Room::with('virtualTour:id,room_id')
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'room_type', 'status']);

        $roomsForReact = $rooms->map(fn($r) => [
            'id'          => $r->id,
            'room_number' => $r->room_number,
            'room_type'   => $r->room_type,
            'status'      => $r->status,
            'has_tour'    => (bool) $r->virtualTour,
        ])->values()->all();

        $stats = $this->statsPayload();

        return view('admin.virtual-tour', compact('tours', 'rooms', 'roomsForReact', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'     => 'required|exists:rooms,id|unique:virtual_tours,room_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('tours', 'public');
            $validated['thumbnail'] = basename($path);
        }

        $tour = VirtualTour::create($validated);
        // Only the columns the payload needs
        $tour->load('room:id,room_number');
        $tour->scenes_count = 0;

        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'tour'    => $this->tourPayload($tour),
                'stats'   => $this->statsPayload(),
                'message' => 'Virtual tour created successfully.',
            ]);
        }

        return redirect()->route('admin.virtual-tour.index')
            ->with('success', 'Virtual tour created successfully.');
    }

    public function update(Request $request, VirtualTour $virtualTour)
    {
        $validated = $request->validate([
            'room_id'     => [
                'sometimes',
                'required',
                'exists:rooms,id',
                Rule::unique('virtual_tours', 'room_id')->ignore($virtualTour->id),
            ],
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        // Capture the old thumbnail BEFORE replacing it so we can delete it after
        $oldThumbnail = $virtualTour->thumbnail;

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('tours', 'public');
            $validated['thumbnail'] = basename($path);
        }

        $virtualTour->update($validated);
        $virtualTour->load('room:id,room_number');

        // Delete the old file after the DB commit — but do it synchronously
        // here since it's a single file (small, fast) and avoids a dangling
        // reference if the process dies. Only the store() call is slow.
        if (
            $request->hasFile('thumbnail')
            && $oldThumbnail
            && !str_starts_with($oldThumbnail, 'http')
        ) {
            Storage::disk('public')->delete('tours/' . $oldThumbnail);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'tour'    => $this->tourPayload($virtualTour->fresh()),
                'stats'   => $this->statsPayload(),
                'message' => 'Virtual tour updated successfully.',
            ]);
        }

        return redirect()->route('admin.virtual-tour.index')
            ->with('success', 'Virtual tour updated successfully.');
    }

    public function destroy(Request $request, VirtualTour $virtualTour)
    {
        $id      = $virtualTour->id;
        $roomId  = $virtualTour->room_id;
        $title   = $virtualTour->title;

        // Collect files BEFORE deleting the model
        $filesToDelete = [];
        if ($virtualTour->thumbnail && !str_starts_with($virtualTour->thumbnail, 'http')) {
            $filesToDelete[] = 'tours/' . $virtualTour->thumbnail;
        }
        foreach ($virtualTour->scenes()->get(['image_path']) as $scene) {
            if ($scene->image_path) {
                $filesToDelete[] = 'scenes/' . $scene->image_path;
            }
        }

        // DB delete is fast — do it first
        $virtualTour->delete();

        // Defer the file deletion to after the response is sent so the
        // frontend gets its JSON back instantly. Uses Laravel's `defer()`
        // helper (Laravel 11+) or falls back to a queued job.
        $this->deferDelete($filesToDelete);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'id'      => $id,
                'room_id' => $roomId,
                'title'   => $title,
                'stats'   => $this->statsPayload(),
                'message' => 'Virtual tour deleted successfully.',
            ]);
        }

        return redirect()->route('admin.virtual-tour.index')
            ->with('success', 'Virtual tour deleted successfully.');
    }

    // ------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------

    /**
     * Delete files after the HTTP response is sent.
     * Uses Laravel's `defer()` if available, otherwise falls back
     * to register_shutdown_function so the response returns immediately.
     */
    private function deferDelete(array $files): void
    {
        if (empty($files)) {
            return;
        }

        $task = function () use ($files) {
            try {
                Storage::disk('public')->delete($files);
            } catch (\Throwable $e) {
                report($e);
            }
        };

        if (function_exists('defer')) {
            defer($task);
        } else {
            // Fallback for older Laravel versions
            register_shutdown_function($task);
        }
    }

    private function tourPayload(VirtualTour $tour): array
    {
        $edit = $tour->toEditArray();
        $edit['hotspots_count']   = $tour->hotspots_count;
        $edit['created_at_label'] = $tour->created_at?->format('M d, Y');
        $edit['scenes_count']     = $tour->scenes_count ?? 0;
        $edit['room_id']          = $tour->room_id;
        return $edit;
    }

    private function statsPayload(): array
    {
        return [
            'total'     => VirtualTour::count(),
            'published' => VirtualTour::published()->count(),
            'draft'     => VirtualTour::draft()->count(),
            'hotspots'  => DB::table('tour_hotspots')
                ->join('tour_scenes', 'tour_hotspots.scene_id', '=', 'tour_scenes.id')
                ->count(),
        ];
    }
}