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
    /**
     * Display the virtual tour manager.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $tours = VirtualTour::with(['room', 'scenes'])
            ->withCount(['scenes as hotspots_count'])
            ->filter($filters)
            ->latest()
            ->get();

        // All rooms (for the Add dropdown)
        $rooms = Room::with('virtualTour')
            ->orderBy('room_number')
            ->get();

        $roomsForReact = $rooms->map(function ($r) {
            return [
                'id'          => $r->id,
                'room_number' => $r->room_number,
                'room_type'   => $r->room_type,
                'status'      => $r->status,
                'has_tour'    => (bool) $r->virtualTour,
            ];
        })->values()->all();

        $stats = [
            'total'     => VirtualTour::count(),
            'published' => VirtualTour::published()->count(),
            'draft'     => VirtualTour::draft()->count(),
            'hotspots'  => DB::table('tour_hotspots')
                ->join('tour_scenes', 'tour_hotspots.scene_id', '=', 'tour_scenes.id')
                ->count(),
        ];

        return view('admin.virtual-tour', compact('tours', 'rooms', 'roomsForReact', 'stats'));
    }

    /**
     * Store a newly created virtual tour.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'     => 'required|exists:rooms,id|unique:virtual_tours,room_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('tours', 'public');
            $validated['thumbnail'] = basename($path);
        }

        VirtualTour::create($validated);

        return redirect()
            ->route('admin.virtual-tour.index')
            ->with('success', 'Virtual tour created successfully.');
    }

    /**
     * Update the specified virtual tour.
     */
    public function update(Request $request, VirtualTour $virtualTour)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Handle thumbnail replacement
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($virtualTour->thumbnail && !str_starts_with($virtualTour->thumbnail, 'http')) {
                Storage::disk('public')->delete('tours/' . $virtualTour->thumbnail);
            }

            $path = $request->file('thumbnail')->store('tours', 'public');
            $validated['thumbnail'] = basename($path);
        }

        $virtualTour->update($validated);

        return redirect()
            ->route('admin.virtual-tour.index')
            ->with('success', 'Virtual tour updated successfully.');
    }

    /**
     * Remove the specified virtual tour.
     */
    public function destroy(VirtualTour $virtualTour)
    {
        // Delete thumbnail
        if ($virtualTour->thumbnail && !str_starts_with($virtualTour->thumbnail, 'http')) {
            Storage::disk('public')->delete('tours/' . $virtualTour->thumbnail);
        }

        // Delete all scene images
        foreach ($virtualTour->scenes as $scene) {
            if ($scene->image_path) {
                Storage::disk('public')->delete('scenes/' . $scene->image_path);
            }
        }

        // Database cascades will remove scenes & hotspots
        $virtualTour->delete();

        return redirect()
            ->route('admin.virtual-tour.index')
            ->with('success', 'Virtual tour deleted successfully.');
    }

    /**
     * Toggle the status of a virtual tour.
     */
    public function toggleStatus(Request $request, VirtualTour $virtualTour)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
        ]);

        $virtualTour->update(['status' => $validated['status']]);

        return redirect()
            ->route('admin.virtual-tour.index')
            ->with('success', 'Tour status updated.');
    }
}
