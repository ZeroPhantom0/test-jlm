<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        $rooms = Room::with(['virtualTour' => function ($q) {
                $q->where('status', 'Published');
            }])
            ->orderByRaw("FIELD(status, 'Available', 'Reserved', 'Occupied')")
            ->orderBy('room_number')
            ->get()
            // 1. Filter out rooms that DO NOT have a Published tour
            ->filter(function ($room) {
                return $room->virtualTour !== null && $room->virtualTour->status === 'Published';
            })
            ->map(function ($room) {
                // 2. Since we filtered, we know virtualTour is guaranteed to be Published here
                $publishedTour = $room->virtualTour;

                return [
                    'id'          => $room->id,
                    'room_number' => $room->room_number,
                    'floor'       => $room->floor,
                    'room_type'   => $room->room_type,
                    'description' => $room->description,
                    'total_beds'  => $room->total_beds,
                    'daily_rent'  => (float) $room->daily_rent,
                    'status'      => $room->status,

                    // Use ONLY the published tour's thumbnail. 
                    // If it's null, the frontend will show the empty gray background.
                    'thumbnail'   => $publishedTour->thumbnail_url,
                    'tour_id'     => $publishedTour->id,
                    'has_tour'    => true,
                    'tour_status' => $publishedTour->status,
                ];
            })
            ->values();

        return Inertia::render('Landing/Index', [
            'rooms' => $rooms,
        ]);
    }
}