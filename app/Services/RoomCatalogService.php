<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * Builds the tenant-facing room cards (availability, bed slots, booking
 * state) shared by the Reservations page and the dashboard room browser,
 * so both always agree on what's bookable.
 */
class RoomCatalogService
{
    /** Placeholder shown when a room has no tour thumbnail. */
    public const PLACEHOLDER_IMG = 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80';

    /**
     * Card image for a room's tour: its thumbnail, else its first 360° scene,
     * resized (see ImageThumb) so cards don't download 8 MB panoramas.
     */
    public static function tourImage(?\App\Models\VirtualTour $tour, int $width = \App\Support\ImageThumb::CARD): string
    {
        if ($tour?->thumbnail) {
            return str_starts_with($tour->thumbnail, 'http')
                ? $tour->thumbnail
                : \App\Support\ImageThumb::url('tours/' . $tour->thumbnail, $width);
        }

        $scene = $tour?->relationLoaded('scenes')
            ? $tour->scenes->sortBy('sort_order')->first()
            : $tour?->scenes()->orderBy('sort_order')->first();

        return $scene?->image_path
            ? \App\Support\ImageThumb::url('scenes/' . $scene->image_path, $width)
            : self::PLACEHOLDER_IMG;
    }

    /** The tenant's own reservations (matched by user id or name, like the Reservations page). */
    public function reservationsFor($user): Collection
    {
        return Reservation::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('tenant_name', $user->name);
        })->latest()->get();
    }

    /** Room the tenant currently lives in, or null if their stay is over / not approved. */
    public function currentRoomId(?Tenant $tenant): ?int
    {
        if (! $tenant || $tenant->status !== 'active' || ! $tenant->room_id) {
            return null;
        }

        $stay = $tenant->approvedReservation;
        if ($stay && ($stay->status !== 'Approved' || $stay->isStayOver())) {
            return null;
        }

        return $tenant->room_id;
    }

    /**
     * Room cards for every room with a published virtual tour, in the
     * Reservations page order (Solo → Barkada → Bedspace, then number).
     */
    public function roomCards(Collection $myReservations, ?int $currentRoomId = null): Collection
    {
        // Real social proof: bookings made in the last 30 days (not cancelled / rejected)
        $recentBookings = Reservation::where('created_at', '>=', now()->subDays(30))
            ->whereNotIn('status', ['Cancelled', 'Rejected'])
            ->selectRaw('room_id, COUNT(*) as c')
            ->groupBy('room_id')
            ->pluck('c', 'room_id');

        return Room::with([
            'virtualTour' => fn ($q) => $q->where('status', 'Published')->with('scenes'),
            'tenants'     => fn ($q) => $q->where('status', 'active')->with(['approvedReservation', 'user']),
        ])
            ->whereHas('virtualTour', fn ($q) => $q->where('status', 'Published'))
            ->orderByRaw("
                CASE
                    WHEN room_type LIKE '%Solo%'     THEN 1
                    WHEN room_type LIKE '%Barkada%'  THEN 2
                    WHEN room_type LIKE '%Bedspace%' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => $this->roomCard($room, $myReservations, $currentRoomId)
                + ['reserved_30d' => (int) ($recentBookings[$room->id] ?? 0)])
            ->values();
    }

    public function roomCard(Room $room, Collection $myReservations, ?int $currentRoomId = null): array
    {
        $bedSlots = $this->bedSlots($room);

        $occupiedSlots = collect($bedSlots)->where('status', 'Occupied')->count();
        $totalBeds     = (int) $room->total_beds;
        $emptyBeds     = max(0, $totalBeds - $occupiedSlots);

        // Does the tenant already have an ACTIVE reservation for this room?
        $existingReservation = $myReservations->first(function ($res) use ($room) {
            return $res->room_id === $room->id
                && ($res->status === 'Pending'
                    || ($res->status === 'Approved' && ! $res->isStayOver()));
        });

        $hasReserved   = (bool) $existingReservation;
        $isCurrentRoom = ($currentRoomId === $room->id);
        // "Reserved" = free now but booked for later dates — other dates
        // can still be booked (overlap is validated per date on submit).
        $isAvailable = $emptyBeds > 0 && in_array($room->status, ['Available', 'Reserved'], true);
        $isBookable  = $isAvailable && ! $hasReserved && ! $isCurrentRoom;

        $availability = $room->availabilityInfo();

        $tenantsList = $room->tenants->map(fn ($tenant) => [
            'name'   => $tenant->user?->name ?? 'Unknown',
            'bed_id' => $tenant->approvedReservation?->room_bed_id ?? 'N/A',
            'status' => $tenant->status,
        ])->values()->toArray();

        $tour    = $room->virtualTour;
        $gallery = collect([$tour?->thumbnail && ! str_starts_with($tour->thumbnail, 'http')
                ? \App\Support\ImageThumb::url('tours/' . $tour->thumbnail, \App\Support\ImageThumb::GALLERY) : null])
            ->merge($tour?->scenes?->sortBy('sort_order')
                ->filter(fn ($s) => $s->image_path)
                ->map(fn ($s) => \App\Support\ImageThumb::url('scenes/' . $s->image_path, \App\Support\ImageThumb::GALLERY)) ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'id'              => $room->id,
            'num'             => $room->room_number,
            'name'            => $room->room_type,
            'price'           => (float) $room->daily_rent,
            'status'          => $room->status,
            'is_available'    => $isAvailable,
            'is_bookable'     => $isBookable,
            'has_reserved'    => $hasReserved,
            'is_current_room' => $isCurrentRoom,
            'empty_beds'      => $emptyBeds,
            'occupied_beds'   => $occupiedSlots,
            'tenants'         => $tenantsList,
            'img'             => self::tourImage($tour),
            'gallery'         => $gallery,
            'pax'             => match (true) {
                str_contains($room->room_type, 'Solo')     => '1-2 pax',
                str_contains($room->room_type, 'Bedspace') => '1 pax',
                str_contains($room->room_type, 'Barkada')  => '4-6 pax',
                default                                    => $room->total_beds . ' pax',
            },
            'beds'            => $room->total_beds . ' bed' . ($room->total_beds === 1 ? '' : 's'),
            'total_beds'      => $totalBeds,
            'bath'            => '1 bath',
            'floor'           => $room->floor,
            'desc'            => $room->description ?? 'Cozy room with modern finishes.',
            'estDate'         => $availability['est_date'],
            'estApprox'       => $availability['est_approx'],
            'nextBooking'     => $availability['next_booking'],
            'tourId'          => $tour?->id,
            'bed_slots'       => $bedSlots,
            // Bedspace is booked per bed; Solo / Barkada rooms are booked whole
            'per_bed'         => $room->room_type === 'Bedspace',
            'created_at'      => $room->created_at?->toIso8601String(),
            'is_female_only'  => str_contains($room->room_type, 'All Girls'),
            'is_male_only'    => str_contains($room->room_type, 'All Boys'),
        ];
    }

    /** Upper/lower bunk slots for Bedspace rooms, marked Occupied when held. */
    public function bedSlots(Room $room): array
    {
        if (in_array($room->room_type, ['Solo Room', 'Barkada Room'], true)) {
            return [];
        }

        $totalBeds = (int) $room->total_beds;
        if ($totalBeds <= 0) {
            return [];
        }

        $heldByTenants = [];
        foreach ($room->tenants as $tenant) {
            $slot = $tenant->approvedReservation?->room_bed_id;
            if ($slot) {
                $heldByTenants[$slot] = true;
            }
        }

        $heldByReservations = array_flip(
            Reservation::where('room_id', $room->id)
                ->whereIn('status', ['Pending', 'Approved'])
                ->whereNotNull('room_bed_id')
                ->where(function ($q) {
                    $q->whereNull('check_out')
                        ->orWhereDate('check_out', '>=', now()->toDateString());
                })
                ->pluck('room_bed_id')
                ->all()
        );

        $slots = [];
        $slotIndex = 0;
        for ($i = 1; $i <= ceil($totalBeds / 2); $i++) {
            foreach (['U' => 'Upper', 'L' => 'Lower'] as $code => $label) {
                if ($slotIndex >= $totalBeds) {
                    break 2;
                }
                $id = "B{$i}-{$code}";
                $occupied = isset($heldByTenants[$id]) || isset($heldByReservations[$id]);

                $slots[] = [
                    'id'     => $id,
                    'label'  => "Bed {$i} - {$label}",
                    'status' => $occupied ? 'Occupied' : 'Available',
                ];
                $slotIndex++;
            }
        }

        return $slots;
    }
}
