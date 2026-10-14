<?php

namespace App\Http\Controllers;

use App\Models\TourScene;
use App\Models\VirtualTour;
use Illuminate\Http\Request;

class PublicTourViewerController extends Controller
{
    public function show(VirtualTour $virtualTour)
    {
        // Only show published tours to the public
        if ($virtualTour->status !== 'Published') {
            abort(404, 'Tour not available.');
        }

        $virtualTour->load('scenes', 'room');

        // Find starting scene (fallback to first scene)
        $startScene = $virtualTour->scenes
            ->sortByDesc('is_starting_scene')
            ->first();

        return view('public.tour-viewer', [
            'tour'        => $virtualTour,
            'startScene'  => $startScene,
        ]);
    }
}