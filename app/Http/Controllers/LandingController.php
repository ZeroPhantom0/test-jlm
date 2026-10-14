<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\VirtualTour;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        // All rooms, ordered so that:
        //   1. Rooms WITH a published virtual tour come first
        //   2. Available rooms next
        //   3. Then everything else
        $rooms = Room::with(['virtualTour' => function ($q) {
                $q->where('status', 'Published');
            }])
            ->orderByRaw("FIELD(status, 'Available', 'Reserved', 'Occupied')")
            ->orderBy('room_number')
            ->get()
            ->map(function ($room) {
                return [
                    'id'              => $room->id,
                    'room_number'     => $room->room_number,
                    'floor'           => $room->floor,
                    'room_type'       => $room->room_type,
                    'description'     => $room->description,
                    'total_beds'      => $room->total_beds,
                    'daily_rent'      => (float) $room->daily_rent,
                    'status'          => $room->status,
                    'thumbnail'       => $room->virtualTour?->thumbnail_url,
                    'tour_id'         => $room->virtualTour?->id,
                    'has_tour'        => (bool) $room->virtualTour,
                ];
            })
            ->values();

        return Inertia::render('Landing/Index', [
            'rooms' => $rooms,
        ]);
    }
}