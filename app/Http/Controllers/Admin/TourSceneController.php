<?php

namespace App\Http\Controllers\Admin;

use App\Support\InputRules;
use App\Http\Controllers\Controller;
use App\Models\TourScene;
use App\Models\VirtualTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TourSceneController extends Controller
{
    /**
     * Show the scenes manager for a tour.
     */
    public function index(VirtualTour $virtualTour)
    {
        return view('admin.scenes', compact('virtualTour'));
    }

    /**
     * Upload a new scene.
     */
    public function store(Request $request, VirtualTour $virtualTour)
    {
        $validated = $request->validate([
            'title'           => InputRules::text(InputRules::TITLE),
            'description'     => InputRules::text(InputRules::LONG_TEXT, false),
            'default_yaw'     => ['nullable', 'numeric', 'between:-360,360'],
            'default_pitch'   => ['nullable', 'numeric', 'between:-90,90'],
            'auto_audio_text' => InputRules::text(InputRules::LONG_TEXT, false),
            'scene_image'     => InputRules::image(true, InputRules::PANORAMA_KB),
        ]);

        // Handle file upload
        $path = $request->file('scene_image')->store('scenes', 'public');
        $filename = basename($path);

        // Is this the first scene?
        $isFirst = $virtualTour->scenes()->count() === 0;

        TourScene::create([
            'tour_id'            => $virtualTour->id,
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'image_path'         => $filename,
            'is_starting_scene'  => $isFirst ? 1 : 0,
            'default_yaw'        => $validated['default_yaw'] ?? 0,
            'default_pitch'      => $validated['default_pitch'] ?? 0,
            'auto_audio_text'    => $validated['auto_audio_text'] ?? null,
        ]);

        return redirect()
            ->route('admin.virtual-tour.scenes.index', $virtualTour->id)
            ->with('success', 'Scene uploaded successfully.');
    }

    /**
     * Update an existing scene.
     */
    public function update(Request $request, VirtualTour $virtualTour, TourScene $scene)
    {
        $validated = $request->validate([
            'title'           => InputRules::text(InputRules::TITLE),
            'description'     => InputRules::text(InputRules::LONG_TEXT, false),
            'default_yaw'     => ['nullable', 'numeric', 'between:-360,360'],
            'default_pitch'   => ['nullable', 'numeric', 'between:-90,90'],
            'auto_audio_text' => InputRules::text(InputRules::LONG_TEXT, false),
            'scene_image'     => InputRules::image(false, InputRules::PANORAMA_KB),
        ]);

        $payload = [
            'title'           => $validated['title'],
            'description'     => $validated['description'] ?? null,
            'default_yaw'     => $validated['default_yaw'] ?? 0,
            'default_pitch'   => $validated['default_pitch'] ?? 0,
            'auto_audio_text' => $validated['auto_audio_text'] ?? null,
        ];

        // Replace image if new one uploaded
        if ($request->hasFile('scene_image')) {
            // Delete old
            if ($scene->image_path) {
                Storage::disk('public')->delete('scenes/' . $scene->image_path);
            }
            $path = $request->file('scene_image')->store('scenes', 'public');
            $payload['image_path'] = basename($path);
        }

        $scene->update($payload);

        return redirect()
            ->route('admin.virtual-tour.scenes.index', $virtualTour->id)
            ->with('success', 'Scene updated successfully.');
    }

    /**
     * Set a scene as the starting scene.
     */
    public function setStarting(VirtualTour $virtualTour, TourScene $scene)
    {
        // Clear all starting flags for this tour
        TourScene::where('tour_id', $virtualTour->id)->update(['is_starting_scene' => 0]);
        // Set this one
        $scene->update(['is_starting_scene' => 1]);

        return redirect()
            ->route('admin.virtual-tour.scenes.index', $virtualTour->id)
            ->with('success', 'Starting scene updated.');
    }

    /**
     * Delete a scene.
     */
    public function destroy(VirtualTour $virtualTour, TourScene $scene)
    {
        // Delete image file
        if ($scene->image_path) {
            Storage::disk('public')->delete('scenes/' . $scene->image_path);
        }

        // Cascade will handle hotspots if FK is set; otherwise delete manually
        $scene->hotspots()->delete();
        $scene->delete();

        return redirect()
            ->route('admin.virtual-tour.scenes.index', $virtualTour->id)
            ->with('success', 'Scene deleted.');
    }
}