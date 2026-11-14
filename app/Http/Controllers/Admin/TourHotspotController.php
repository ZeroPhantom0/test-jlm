<?php

namespace App\Http\Controllers\Admin;

use App\Support\InputRules;
use App\Http\Controllers\Controller;
use App\Models\TourHotspot;
use App\Models\TourScene;
use App\Models\VirtualTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TourHotspotController extends Controller
{
    /**
     * Return scene + hotspots for the builder viewer.
     * GET /admin/virtual-tour/{virtualTour}/builder/data?scene_id=X
     */
    public function getTourData(Request $request, VirtualTour $virtualTour)
    {
        $request->validate(['scene_id' => ['required', 'integer']]);
        $sceneId = $request->query('scene_id');

        $scene = TourScene::where('tour_id', $virtualTour->id)
            ->findOrFail($sceneId);

        $hotspots = TourHotspot::where('scene_id', $scene->id)->get();

        return response()->json([
            'scene'    => $scene,
            'hotspots' => $hotspots,
        ]);
    }

    /**
     * Create a new hotspot.
     * POST /admin/virtual-tour/{virtualTour}/builder/hotspots
     */
    public function store(Request $request, VirtualTour $virtualTour)
    {
        $validated = $request->validate([
            'scene_id'        => ['required', 'integer', 'exists:tour_scenes,id'],
            'type'            => 'required|in:navigation,info,audio,image,video,link',
            'tooltip'         => InputRules::text(InputRules::TITLE, false),
            // Panorama coordinates in degrees
            'pitch'           => ['required', 'numeric', 'between:-90,90'],
            'yaw'             => ['required', 'numeric', 'between:-360,360'],
            'target_scene_id' => ['nullable', 'integer', 'exists:tour_scenes,id'],
            'content_text'    => InputRules::text(InputRules::LONG_TEXT, false),
            'media_url'       => InputRules::url(),
            'nav_audio'       => InputRules::text(InputRules::LONG_TEXT, false),
            'description'     => InputRules::text(InputRules::LONG_TEXT, false),
            // Font Awesome class, e.g. "fa-solid fa-door-open"
            'icon'            => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\- ]+$/'],
        ]);

        // Handle fields that may not exist in your current schema.
        // The migration only needs to exist if you actually want to store them.
        $payload = [
            'scene_id'        => $validated['scene_id'],
            'type'            => $validated['type'],
            'tooltip'         => $validated['tooltip'] ?? '',
            'pitch'           => $validated['pitch'],
            'yaw'             => $validated['yaw'],
            'target_scene_id' => $validated['target_scene_id'] ?? null,
            'content_text'    => $validated['content_text'] ?? null,
            'media_url'       => $validated['media_url'] ?? null,
            'nav_audio'       => $validated['nav_audio'] ?? null,
        ];

        // Optional columns — only add if they exist in DB
        if ($this->columnExists('tour_hotspots', 'icon')) {
            $payload['icon'] = $validated['icon'] ?? null;
        }
        if ($this->columnExists('tour_hotspots', 'description')) {
            $payload['description'] = $validated['description'] ?? null;
        }

        $hotspot = TourHotspot::create($payload);

        return response()->json(['success' => true, 'id' => $hotspot->id]);
    }

    /**
     * Update an existing hotspot.
     * PUT/POST /admin/virtual-tour/{virtualTour}/builder/hotspots/{hotspot}
     */
    public function update(Request $request, VirtualTour $virtualTour, TourHotspot $hotspot)
    {
        $validated = $request->validate([
            'type'            => 'required|in:navigation,info,audio,image,video,link',
            'tooltip'         => InputRules::text(InputRules::TITLE, false),
            // Panorama coordinates in degrees
            'pitch'           => ['required', 'numeric', 'between:-90,90'],
            'yaw'             => ['required', 'numeric', 'between:-360,360'],
            'target_scene_id' => ['nullable', 'integer', 'exists:tour_scenes,id'],
            'content_text'    => InputRules::text(InputRules::LONG_TEXT, false),
            'media_url'       => InputRules::url(),
            'nav_audio'       => InputRules::text(InputRules::LONG_TEXT, false),
            'description'     => InputRules::text(InputRules::LONG_TEXT, false),
            // Font Awesome class, e.g. "fa-solid fa-door-open"
            'icon'            => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\- ]+$/'],
        ]);

        $payload = [
            'type'            => $validated['type'],
            'tooltip'         => $validated['tooltip'] ?? '',
            'pitch'           => $validated['pitch'],
            'yaw'             => $validated['yaw'],
            'target_scene_id' => $validated['target_scene_id'] ?? null,
            'content_text'    => $validated['content_text'] ?? null,
            'media_url'       => $validated['media_url'] ?? null,
            'nav_audio'       => $validated['nav_audio'] ?? null,
        ];

        if ($this->columnExists('tour_hotspots', 'icon')) {
            $payload['icon'] = $validated['icon'] ?? null;
        }
        if ($this->columnExists('tour_hotspots', 'description')) {
            $payload['description'] = $validated['description'] ?? null;
        }

        $hotspot->update($payload);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a hotspot.
     * DELETE /admin/virtual-tour/{virtualTour}/builder/hotspots/{hotspot}
     */
    public function destroy(Request $request, VirtualTour $virtualTour, TourHotspot $hotspot)
    {
        $hotspot->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Helper: check if a column exists (so we don't crash if optional columns are missing).
     */
    private function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}