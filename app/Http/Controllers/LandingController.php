<?php
// app/Http/Controllers/LandingController.php

namespace App\Http\Controllers;

use App\Models\Room;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        // 1. Check if the user is logged in
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // 2. If they are an Admin, send them to the Admin Dashboard
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            // 3. If they are a Tenant, check onboarding status
            if ($user->isTenant()) {
                $hasPersonal = $user->personalInformation()->exists();
                $hasIdentity = $user->identityVerification()->exists();

                if ($hasPersonal && $hasIdentity) {
                    return redirect()->route('tenants.dashboard');
                }

                return redirect()->route('personal-information');
            }
        }

        // 4. If not logged in, show the landing page as normal
        $rooms = Room::with(['virtualTour' => function ($q) {
                $q->where('status', 'Published');
            }])
            ->orderByRaw("FIELD(status, 'Available', 'Reserved', 'Occupied')")
            ->orderBy('room_number')
            ->get()
            // Filter out rooms that DO NOT have a Published tour
            ->filter(function ($room) {
                return $room->virtualTour !== null && $room->virtualTour->status === 'Published';
            })
            ->map(function ($room) {
                // Since we filtered, we know virtualTour is guaranteed to be Published here
                $publishedTour = $room->virtualTour;
                $availability  = $room->availabilityInfo();

                return [
                    // Occupied → nearest check-out of current occupants;
                    // Reserved → dates of the upcoming booking
                    'est_available' => $availability['est_date'],
                    'est_approx'    => $availability['est_approx'],
                    'next_booking'  => $availability['next_booking'],
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