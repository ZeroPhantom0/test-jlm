<?php
// app/Http/Controllers/Tenant/TenantReservationController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantReservationController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        $myReservations = Reservation::where(function ($q) use ($user, $tenant) {
            $q->where('user_id', $user->id)
                ->orWhere('tenant_name', $user->name);
        })
            ->latest()
            ->get();

        $reservationCards = $myReservations->map(function ($r) {
            $statusClass = match ($r->status) {
                'Approved' => 'bg-emerald-100 text-emerald-600',
                'Rejected' => 'bg-rose-100 text-rose-600',
                'Expired'  => 'bg-slate-100 text-slate-500',
                default    => 'bg-amber-100 text-amber-700',
            };
            $statusIcon = match ($r->status) {
                'Approved' => 'fa-regular fa-circle-check',
                'Rejected' => 'fa-regular fa-circle-xmark',
                'Expired'  => 'fa-regular fa-clock',
                default    => 'fa-regular fa-clock',
            };

            return [
                'id'          => $r->id,
                'key'         => $r->reference_no,
                'room'        => $r->desired_room,
                'status'      => $r->status,
                'statusClass' => $statusClass,
                'statusIcon'  => $statusIcon,
                'move_in'     => $r->check_in?->format('M j, Y') ?? '—',
                'move_out'    => $r->check_out?->format('M j, Y') ?? '—',
                'submitted'   => $r->created_at?->format('M j, Y') ?? '—',
                'deposit'     => number_format((float) $r->reservation_fee, 2),
                'note'        => $r->admin_notes,
            ];
        });

        $rooms = Room::with([
            'virtualTour' => fn($q) => $q->where('status', 'Published'),
            'tenants'     => fn($q) => $q->where('status', 'active')->with('approvedReservation'),
        ])
            ->whereHas('virtualTour', fn($q) => $q->where('status', 'Published'))
            ->orderBy('room_number')
            ->get()
            ->map(function (Room $room) use ($myReservations) {
                $bedSlots = $this->buildBedSlots($room);
                
                // Calculate actual empty beds
                $occupiedSlots = collect($bedSlots)->where('status', 'Occupied')->count();
                $totalBeds = (int) $room->total_beds;
                $emptyBeds = max(0, $totalBeds - $occupiedSlots);

                // Check if this user already has an active/pending reservation for this room
                $existingReservation = $myReservations->first(function ($res) use ($room) {
                    return $res->room_id === $room->id && in_array($res->status, ['Pending', 'Approved']);
                });
                $hasReserved = (bool) $existingReservation;

                // Room is bookable if it has empty beds AND user hasn't reserved it
                $isBookable = ($emptyBeds > 0 || $room->status === 'Available') && !$hasReserved;

                return [
                    'id'          => $room->id,
                    'num'         => $room->room_number,
                    'name'        => $room->room_type,
                    'price'       => (float) $room->daily_rent,
                    'status'      => $room->status,
                    'is_bookable' => $isBookable,
                    'has_reserved'=> $hasReserved,
                    'empty_beds'  => $emptyBeds,
                    'img'         => $room->virtualTour?->thumbnail_url
                        ?? 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80',
                    'pax'         => match (true) {
                        str_contains($room->room_type, 'Solo')     => '1-2 pax',
                        str_contains($room->room_type, 'Bedspace') => '1 pax',
                        str_contains($room->room_type, 'Barkada')  => '4-6 pax',
                        default                                    => $room->total_beds . ' pax',
                    },
                    'beds'        => $room->total_beds . ' bed' . ($room->total_beds === 1 ? '' : 's'),
                    'total_beds'  => (int) $room->total_beds,
                    'bath'        => '1 bath',
                    'floor'       => $room->floor,
                    'desc'        => $room->description ?? 'Cozy room with modern finishes.',
                    'estDate'     => null,
                    'estApprox'   => null,
                    'tourId'      => $room->virtualTour?->id,
                    'bed_slots'   => $bedSlots,
                    'is_female_only' => str_contains($room->room_type, 'All Girls'),
                    'is_male_only'   => str_contains($room->room_type, 'All Boys'),
                ];
            });

        $myReservationsForReact = $myReservations->mapWithKeys(function ($r) {
            return [
                $r->reference_no => [
                    'id'      => $r->id,
                    'key'     => $r->reference_no,
                    'status'  => $r->status,
                    'room'    => $r->desired_room,
                    'deposit' => $r->formatted_fee,
                ],
            ];
        })->toArray();

        return view('tenants.reservations', [
            'tenant'         => $tenant,
            'myReservations' => $reservationCards,
            'reservations'   => $myReservationsForReact,
            'rooms'          => $rooms,
        ]);
    }

    private function buildBedSlots(Room $room): array
    {
        if ($room->room_type === 'Solo Room') {
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

        $heldByReservations = Reservation::where('room_id', $room->id)
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereNotNull('room_bed_id')
            ->where(function ($q) {
                $q->whereNull('check_out')
                    ->orWhereDate('check_out', '>=', now()->toDateString());
            })
            ->pluck('room_bed_id')
            ->all();
        $heldByReservations = array_flip($heldByReservations);

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

    private function computeDueDate(Carbon $checkIn, Carbon $checkOut): Carbon
    {
        $days = max(1, $checkIn->diffInDays($checkOut));

        if ($days <= 1) {
            return $checkOut->copy();
        }
        if ($days <= 6) {
            return $checkIn->copy()->addDays(3);
        }
        if ($days <= 31) {
            return $checkIn->copy()->addDays(7);
        }
        if ($days <= 100) {
            return $checkIn->copy()->addMonths(1);
        }
        if ($days <= 364) {
            return $checkIn->copy()->addMonths(2);
        }
        return $checkIn->copy()->addMonths(3);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'room_id'         => ['required', 'exists:rooms,id'],
            'check_in_date'   => ['required', 'date'],
            'check_in_time'   => ['nullable', 'string', 'max:20'],
            'check_out_date'  => ['required', 'date', 'after:check_in_date'],
            'check_out_time'  => ['nullable', 'string', 'max:20'],
            'has_companion'   => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string'],
            'payment_option'  => ['required', 'in:half,full'],
            'reference_no'    => ['required', 'string', 'max:100'],
            'receipt'         => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'room_bed_id'     => ['nullable', 'string', 'max:50'],
            'agreed_to_terms' => ['accepted'],
        ]);

        $inDate  = Carbon::parse($validated['check_in_date']  . ' ' . ($validated['check_in_time']  ?? '00:00'));
        $outDate = Carbon::parse($validated['check_out_date'] . ' ' . ($validated['check_out_time'] ?? '00:00'));

        if ($inDate->diffInHours($outDate) < 24) {
            return response()->json([
                'message' => 'The minimum stay is 24 hours (1 full day). Please extend your check-out date.',
            ], 422);
        }

        // Prevent double booking by checking if they already have a reservation
        $existing = Reservation::where('user_id', $user->id)
            ->where('room_id', $validated['room_id'])
            ->whereIn('status', ['Pending', 'Approved'])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'You already have an active or pending reservation for this room.',
            ], 422);
        }

        $recentDuplicate = Reservation::where('user_id', $user->id)
            ->where('room_id', $validated['room_id'])
            ->where('status', 'Pending')
            ->whereDate('check_in', $validated['check_in_date'])
            ->whereDate('check_out', $validated['check_out_date'])
            ->where('payment_option', $validated['payment_option'] === 'half' ? 'Down Payment' : 'Full Payment')
            ->where('created_at', '>=', now()->subSeconds(30))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            return response()->json([
                'success'    => true,
                'idempotent' => true,
                'message'    => 'This reservation was already submitted.',
                'data'       => ['reference_no' => $recentDuplicate->reference_no],
            ], 200);
        }

        $receiptPath = $request->file('receipt')
            ->store('reservations/' . $user->id, 'public');

        try {
            $reservation = DB::transaction(function () use ($validated, $user, $receiptPath) {

                $room = Room::where('id', $validated['room_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $in  = Carbon::parse($validated['check_in_date']);
                $out = Carbon::parse($validated['check_out_date']);

                if ($out->lte($in)) {
                    throw new \RuntimeException('Check-out must be after check-in.');
                }

                $isBedspaceType = in_array($room->room_type, ['Bedspace', 'Barkada Room'], true);

                if ($isBedspaceType && empty($validated['room_bed_id'])) {
                    throw new \RuntimeException(
                        'Please select a bed slot for this room type.'
                    );
                }

                if ($isBedspaceType) {
                    $validIds = array_column($this->buildBedSlots($room), 'id');
                    if (! in_array($validated['room_bed_id'], $validIds, true)) {
                        throw new \RuntimeException('Invalid bed slot selected.');
                    }

                    $bedTaken = Reservation::where('room_id', $room->id)
                        ->where('room_bed_id', $validated['room_bed_id'])
                        ->whereIn('status', ['Pending', 'Approved'])
                        ->where(function ($q) use ($in, $out) {
                            $q->whereDate('check_in', '<', $out->toDateString())
                                ->where(function ($q2) use ($in) {
                                    $q2->whereDate('check_out', '>', $in->toDateString())
                                        ->orWhereNull('check_out');
                                });
                        })
                        ->exists();

                    if ($bedTaken) {
                        throw new \RuntimeException(
                            'That bed slot is already booked for the selected dates. Please pick a different bed.'
                        );
                    }
                } else {
                    $overlap = Reservation::where('room_id', $room->id)
                        ->whereIn('status', ['Pending', 'Approved'])
                        ->where(function ($q) use ($in, $out) {
                            $q->whereDate('check_in', '<', $out->toDateString())
                                ->where(function ($q2) use ($in) {
                                    $q2->whereDate('check_out', '>', $in->toDateString())
                                        ->orWhereNull('check_out');
                                });
                        })
                        ->exists();

                    if ($overlap) {
                        throw new \RuntimeException(
                            'This room is already reserved for the selected dates. Please pick another room or dates.'
                        );
                    }
                }

                $days = max(1, $in->diffInDays($out));

                $roomRate     = (float) $room->daily_rent;
                $companionFee = ($validated['has_companion'] ?? false) ? 150 : 0;
                $totalCharges = ($roomRate * $days) + $companionFee;

                $payNow    = $validated['payment_option'] === 'half'
                    ? $totalCharges / 2
                    : $totalCharges;
                $remaining = $totalCharges - $payNow;

                $dueDate = $this->computeDueDate($in, $out);

                $avatar = Reservation::makeAvatar($user->name);

                $reservation = Reservation::create([
                    'reference_no'       => Reservation::generateReferenceNo(),
                    'reservation_number' => Reservation::generateReservationNumber(),
                    'user_id'            => $user->id,
                    'room_id'            => $room->id,
                    'tenant_name'        => $user->name,
                    'tenant_initial'     => $avatar['initial'],
                    'avatar_bg'          => $avatar['bg'],
                    'avatar_color'       => $avatar['color'],
                    'email'              => $user->email,
                    'phone'              => $user->phone,
                    'desired_room'       => $room->room_number,
                    'room_bed_id'        => $validated['room_bed_id'] ?? null,
                    'check_in'           => $validated['check_in_date'],
                    'check_in_time'      => $validated['check_in_time'] ?? null,
                    'check_out'          => $validated['check_out_date'],
                    'check_out_time'     => $validated['check_out_time'] ?? null,
                    'has_companion'      => (bool) ($validated['has_companion'] ?? false),
                    'total_charges'      => $totalCharges,
                    'reservation_fee'    => $payNow,
                    'payment_option'     => $validated['payment_option'] === 'half' ? 'Down Payment' : 'Full Payment',
                    'payment_method'     => 'GCash',
                    'remaining'          => $remaining,
                    'request_details'    => $validated['notes'] ?? null,
                    'proof_of_payment'   => $receiptPath,
                    'status'             => 'Pending',
                ]);

                $tenant = Tenant::where('user_id', $user->id)->first();

                Invoice::create([
                    'transaction_no' => Invoice::generateTransactionNo(),
                    'tenant_id'      => $tenant?->id,
                    'room_id'        => $reservation->room_id,
                    'tenant_name'    => $reservation->tenant_name,
                    'room_number'    => $room->room_number,
                    'description'    => 'Reservation Down Payment — ' . $reservation->reference_no,
                    'check_in'       => $reservation->check_in,
                    'check_out'      => $reservation->check_out,
                    'rate_per_day'   => $roomRate,
                    'days'           => $days,
                    'total_amount'   => $totalCharges,
                    'amount_paid'    => $payNow,
                    'balance'        => $remaining,
                    'due_date'       => $dueDate->toDateString(),
                    'payment_method' => 'GCash',
                    'status'         => 'Pending',
                    'receipt_no'     => $validated['reference_no'],
                    'receipt_path'   => $receiptPath,
                    'date_paid'      => now()->toDateString(),
                ]);

                return $reservation;
            });

            return response()->json([
                'success' => true,
                'message' => 'Reservation submitted. Awaiting admin approval.',
                'data'    => ['reference_no' => $reservation->reference_no],
            ]);
        } catch (\RuntimeException $e) {
            if (Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if (Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            report($e);
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    public function cancel(Reservation $reservation)
    {
        $user = Auth::user();

        if ($reservation->user_id !== $user->id) {
            abort(403, 'You can only cancel your own reservations.');
        }

        if ($reservation->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending reservations can be cancelled.',
            ], 422);
        }

        DB::transaction(function () use ($reservation) {
            if ($reservation->proof_of_payment) {
                Storage::disk('public')->delete($reservation->proof_of_payment);
            }

            $reservation->update(['status' => 'Rejected']);

            Invoice::where('tenant_name', $reservation->tenant_name)
                ->where('description', 'like', '%' . $reservation->reference_no . '%')
                ->update([
                    'status'       => 'Unpaid',
                    'receipt_path' => null,
                    'date_paid'    => null,
                ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled.',
        ]);
    }
}