<?php
// app/Http/Controllers/Tenant/TenantReservationController.php

namespace App\Http\Controllers\Tenant;

use App\Events\InvoiceUpdated;
use App\Events\ReservationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Codesmiths\LaravelOcrSpace\Facades\OcrSpace;
use Codesmiths\LaravelOcrSpace\OcrSpaceOptions;
use Codesmiths\LaravelOcrSpace\Enums\Language;
use Codesmiths\LaravelOcrSpace\Enums\OcrSpaceEngine;

class TenantReservationController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        $allMyReservations = Reservation::where(function ($q) use ($user, $tenant) {
            $q->where('user_id', $user->id)
                ->orWhere('tenant_name', $user->name);
        })
            ->latest()
            ->get();

        $pendingReservations = $allMyReservations->where('status', 'Pending')->values();

        $reservationCards = $this->buildReservationCards($pendingReservations);

        // PASS CURRENT ROOM ID TO PREVENT RESERVING IT AGAIN
        $rooms = $this->buildRoomCards($allMyReservations, $this->currentRoomId($tenant));

        $myReservationsForReact = $allMyReservations->mapWithKeys(function ($r) {
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

    public function data()
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        $allMyReservations = Reservation::where(function ($q) use ($user, $tenant) {
            $q->where('user_id', $user->id)
                ->orWhere('tenant_name', $user->name);
        })
            ->latest()
            ->get();

        $pendingReservations = $allMyReservations->where('status', 'Pending')->values();

        $reservationCards = $this->buildReservationCards($pendingReservations);

        // PASS CURRENT ROOM ID
        $rooms = $this->buildRoomCards($allMyReservations, $this->currentRoomId($tenant));

        $myReservationsForReact = $allMyReservations->mapWithKeys(function ($r) {
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

        return response()->json([
            'myReservations' => $reservationCards,
            'rooms'          => $rooms,
            'reservations'   => $myReservationsForReact,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function buildReservationCards($myReservations)
    {
        return $myReservations->map(function ($r) {
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
        })->values();
    }

    /**
     * The tenant's room only counts as "Current Room" while they're active
     * on an Approved stay that hasn't passed check-out yet. An expired /
     * cancelled stay must not keep blocking (or labelling) the room.
     */
    private function currentRoomId(?Tenant $tenant): ?int
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

    private function buildRoomCards($myReservations, ?int $currentRoomId = null)
    {
        return Room::with([
            'virtualTour' => fn($q) => $q->where('status', 'Published'),
            'tenants'     => fn($q) => $q->where('status', 'active')->with('approvedReservation'),
        ])
            ->whereHas('virtualTour', fn($q) => $q->where('status', 'Published'))
            // Sort by room type priority: Solo → Barkada → Bedspace → others, then room number
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
            ->map(function (Room $room) use ($myReservations, $currentRoomId) {
                $bedSlots = $this->buildBedSlots($room);

                $occupiedSlots = collect($bedSlots)->where('status', 'Occupied')->count();
                $totalBeds     = (int) $room->total_beds;
                $emptyBeds     = max(0, $totalBeds - $occupiedSlots);

                // Check if user has an ACTIVE reservation for this room
                $existingReservation = $myReservations->first(function ($res) use ($room) {
                    return $res->room_id === $room->id
                        && ($res->status === 'Pending'
                            || ($res->status === 'Approved' && ! $res->isStayOver()));
                });

                $hasReserved = (bool) $existingReservation;
                $isCurrentRoom = ($currentRoomId === $room->id);
                // "Reserved" = free now but booked for later dates — other dates
                // can still be booked (overlap is validated per date on submit).
                $isBookable = ($emptyBeds > 0 && in_array($room->status, ['Available', 'Reserved'], true))
                    && !$hasReserved && !$isCurrentRoom;

                $availability = $room->availabilityInfo();

                // FOR THE MODAL: Build the tenants list
                $tenantsList = $room->tenants->map(function ($tenant) {
                    return [
                        'name' => $tenant->user?->name ?? 'Unknown',
                        'bed_id' => $tenant->approvedReservation?->room_bed_id ?? 'N/A',
                        'status' => $tenant->status,
                    ];
                })->values()->toArray();

                return [
                    'id'          => $room->id,
                    'num'         => $room->room_number,
                    'name'        => $room->room_type,
                    'price'       => (float) $room->daily_rent,
                    'status'      => $room->status,
                    'is_bookable' => $isBookable,
                    'has_reserved' => $hasReserved,
                    'is_current_room' => $isCurrentRoom,
                    'empty_beds'  => $emptyBeds,
                    'occupied_beds' => $occupiedSlots, // ADDED for Modal
                    'tenants'     => $tenantsList,     // ADDED for Modal
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
                    // Occupied → nearest check-out among current occupants
                    'estDate'     => $availability['est_date'],
                    'estApprox'   => $availability['est_approx'],
                    'nextBooking' => $availability['next_booking'],
                    'tourId'      => $room->virtualTour?->id,
                    'bed_slots'   => $bedSlots,
                    'is_female_only' => str_contains($room->room_type, 'All Girls'),
                    'is_male_only'   => str_contains($room->room_type, 'All Boys'),
                ];
            })->values();
    }

    private function buildBedSlots(Room $room): array
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
        // The balance is now due upon arrival (check-in date)
        return $checkIn->copy();
    }

    public function store(Request $request)
    {
        session()->save();

        $user = Auth::user();

        // ── Timing helper ──────────────────────────────────────
        $t = [];
        $mark = function (string $label) use (&$t) {
            $t[$label] = microtime(true);
        };
        $lap = function (string $label, string $from) use (&$t) {
            $ms = isset($t[$from])
                ? round((microtime(true) - $t[$from]) * 1000)
                : null;
            Log::channel('single')->info('[reservation.timing] ' . $label, [
                'ms'       => $ms,
                'user_id'  => Auth::id(),
                'peak_mem' => round(memory_get_peak_usage(true) / 1048576, 2) . 'MB',
            ]);
        };

        $mark('start');

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
            'num_occupants'   => ['nullable', 'integer', 'min:1'],
            'companion_names' => ['nullable', 'string'],
        ]);
        $lap('validate', 'start');

        $inDate  = Carbon::parse($validated['check_in_date']  . ' ' . ($validated['check_in_time']  ?? '00:00'));
        $outDate = Carbon::parse($validated['check_out_date'] . ' ' . ($validated['check_out_time'] ?? '00:00'));

        if ($inDate->diffInHours($outDate) < 24) {
            return response()->json([
                'message' => 'The minimum stay is 24 hours (1 full day). Please extend your check-out date.',
            ], 422);
        }

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
        $lap('prechecks', 'validate');

        // ── File upload timing ─────────────────────────────────
        $mark('upload_start');
        $receiptPath = $request->file('receipt')
            ->store('reservations/' . $user->id, 'public');
        $lap('upload', 'upload_start');

        try {
            // ── CLEANUP PREVIOUS PENDING RESERVATIONS & INVOICES ──
            // This prevents the "ghost 2 pending payments" issue by removing
            // any old abandoned reservations before creating the new one.
            $oldPendingReservations = Reservation::where('user_id', $user->id)
                ->where('status', 'Pending')
                ->get();

            foreach ($oldPendingReservations as $oldRes) {
                // Delete associated invoices
                $oldInvoices = Invoice::where('description', 'like', '%' . $oldRes->reference_no . '%')
                    ->whereIn('status', ['Pending', 'Unpaid', 'Partial'])
                    ->get();

                foreach ($oldInvoices as $oldInv) {
                    if ($oldInv->receipt_path && Storage::disk('public')->exists($oldInv->receipt_path)) {
                        Storage::disk('public')->delete($oldInv->receipt_path);
                    }
                    $oldInv->forceDelete();
                }

                // Delete old proof of payment
                if ($oldRes->proof_of_payment && Storage::disk('public')->exists($oldRes->proof_of_payment)) {
                    Storage::disk('public')->delete($oldRes->proof_of_payment);
                }

                // Mark old reservation as rejected so it clears out of the way
                $oldRes->update(['status' => 'Rejected']);
            }
            // ──────────────────────────────────────────────────────

            // ── DB transaction timing ──────────────────────────
            $mark('db_start');

            [$reservation, $invoice] = DB::transaction(function () use ($validated, $user, $receiptPath) {

                $room = Room::where('id', $validated['room_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $in  = Carbon::parse($validated['check_in_date']);
                $out = Carbon::parse($validated['check_out_date']);

                if ($out->lte($in)) {
                    throw new \RuntimeException('Check-out must be after check-in.');
                }

                // ── TYPE CHECK ──
                $isBedspaceType = $room->room_type === 'Bedspace';
                $isBarkadaType  = $room->room_type === 'Barkada Room';

                // ── BEDSPACE VALIDATION (Strict) ──
                if ($isBedspaceType) {
                    if (empty($validated['room_bed_id'])) {
                        throw new \RuntimeException('Please select a bed slot for this room type.');
                    }

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
                }
                // ── BARKADA VALIDATION ──
                else if ($isBarkadaType) {
                    // No bed slot required — the group books the whole room,
                    // so no other booking may overlap these dates.
                    $validated['room_bed_id'] = null;

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
                // ── STANDARD ROOM VALIDATION ──
                else {
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
                $roomRate = (float) $room->daily_rent;

                // ── PRICING CALCULATION ──
                if ($isBarkadaType) {
                    $occupants = (int) ($validated['num_occupants'] ?? 1);
                    $fixedBeds = min($occupants, $room->total_beds);
                    $extraBeds = max(0, $occupants - $room->total_beds);

                    // Barkada: the daily rent is a flat rate for the whole room
                    // (good for total_beds people); each person beyond that
                    // adds ₱150 per night.
                    //   e.g. ₱1,000 × 5 nights + (1 extra × ₱150 × 5) = ₱5,750
                    $nightlyRate = $roomRate + ($extraBeds * 150);
                    $totalCharges = $nightlyRate * $days;
                } else {
                    $companionFee = ($validated['has_companion'] ?? false) ? 150 : 0;
                    $totalCharges = ($roomRate * $days) + $companionFee;
                }

                $payNow    = $validated['payment_option'] === 'half'
                    ? $totalCharges / 2
                    : $totalCharges;
                $remaining = $totalCharges - $payNow;

                // A remaining balance (down payment) is due at check-in — the
                // payment-policy deadline. Full payments keep the usual rule.
                $dueDate = $remaining > 0.005
                    ? $in->copy()
                    : $this->computeDueDate($in, $out);

                $avatar = Reservation::makeAvatar($user->name);

                // ── REQUEST DETAILS BUILDER ──
                $requestDetails = $validated['notes'] ?? '';
                if ($isBarkadaType && !empty($validated['companion_names'])) {
                    $names = json_decode($validated['companion_names'], true);
                    if (is_array($names) && count($names) > 0) {
                        $requestDetails .= "\n\n[Barkada Occupants]:\n";
                        $requestDetails .= "Total Occupants: " . ($validated['num_occupants'] ?? 1) . "\n";
                        $requestDetails .= "Companions: " . implode(', ', array_filter($names));
                    }
                }

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
                    'has_companion'      => $isBarkadaType ? true : (bool) ($validated['has_companion'] ?? false),
                    'total_charges'      => $totalCharges,
                    'reservation_fee'    => $payNow,
                    'payment_option'     => $validated['payment_option'] === 'half' ? 'Down Payment' : 'Full Payment',
                    'payment_method'     => 'GCash',
                    'remaining'          => $remaining,
                    'request_details'    => trim($requestDetails),
                    'proof_of_payment'   => $receiptPath,
                    'status'             => 'Pending',
                ]);

                $tenant = Tenant::where('user_id', $user->id)->first();

                $invoice = Invoice::create([
                    'transaction_no' => Invoice::generateTransactionNo(),
                    'tenant_id'      => $tenant?->id,
                    'room_id'        => $reservation->room_id,
                    'tenant_name'    => $reservation->tenant_name,
                    'room_number'    => $room->room_number,
                    'description'    => 'Reservation Down Payment — ' . $reservation->reference_no,
                    'check_in'       => $reservation->check_in,
                    'check_out'      => $reservation->check_out,
                    'rate_per_day'   => $isBarkadaType ? ($totalCharges / max(1, $days)) : $roomRate,
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

                return [$reservation, $invoice];
            });
            $lap('db_transaction', 'db_start');

            // ── Broadcast event timing ────
            $mark('events_start');
            event(new ReservationUpdated('created', $reservation->id));
            $lap('event_reservation', 'events_start');

            $mark('event_invoice_start');
            event(new InvoiceUpdated('created', $invoice->id));
            $lap('event_invoice', 'event_invoice_start');

            $mark('response_start');

            return response()->json([
                'success' => true,
                'message' => 'Reservation submitted. Awaiting admin approval.',
                'data'    => ['reference_no' => $reservation->reference_no],
            ])->header('Connection', 'close');
        } catch (\RuntimeException $e) {
            if (Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            Log::info('[reservation.timing] FAILED_RUNTIME', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if (Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            report($e);
            Log::info('[reservation.timing] FAILED_EXCEPTION', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        } finally {
            if (isset($t['start'])) {
                Log::channel('single')->info('[reservation.timing] TOTAL', [
                    'ms'      => round((microtime(true) - $t['start']) * 1000),
                    'user_id' => Auth::id(),
                ]);
            }
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

        $t0 = microtime(true);
        $affectedInvoiceIds = [];

        DB::transaction(function () use ($reservation, &$affectedInvoiceIds) {
            if ($reservation->proof_of_payment) {
                Storage::disk('public')->delete($reservation->proof_of_payment);
            }

            $reservation->update(['status' => 'Rejected']);

            $affectedInvoiceIds = Invoice::where('tenant_name', $reservation->tenant_name)
                ->where('description', 'like', '%' . $reservation->reference_no . '%')
                ->pluck('id')
                ->all();

            if (! empty($affectedInvoiceIds)) {
                Invoice::whereIn('id', $affectedInvoiceIds)
                    ->update([
                        'status'       => 'Unpaid',
                        'receipt_path' => null,
                        'date_paid'    => null,
                    ]);
            }
        });

        Log::info('[reservation.timing] cancel_db', [
            'ms'      => round((microtime(true) - $t0) * 1000),
            'user_id' => $user->id,
        ]);

        $t1 = microtime(true);
        event(new ReservationUpdated('cancelled', $reservation->id));
        Log::info('[reservation.timing] cancel_event', [
            'ms'      => round((microtime(true) - $t1) * 1000),
            'user_id' => $user->id,
        ]);

        if (count($affectedInvoiceIds) === 1) {
            event(new InvoiceUpdated('updated', $affectedInvoiceIds[0]));
        } elseif (count($affectedInvoiceIds) > 1) {
            event(new InvoiceUpdated('updated', null));
        }

        Log::info('[reservation.timing] cancel_total', [
            'ms'      => round((microtime(true) - $t0) * 1000),
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled.',
        ]);
    }

    public function parseReceipt(Request $request)
    {
        $request->validate([
            'receipt' => 'required|image|max:5120',
        ]);

        try {
            $file = $request->file('receipt');
            $apiKey = config('ocr-space.api_key') ?? env('OCR_SPACE_API_KEY');

            if (empty($apiKey)) {
                Log::error('[parseReceipt] OCR API key is missing.');
                return response()->json([
                    'success' => false,
                    'error'   => 'OCR service is not configured. Please enter the reference number manually.',
                ], 500);
            }

            $response = Http::timeout(30)
                ->asMultipart()
                ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
                ->post(config('ocr-space.api_url', 'https://api.ocr.space/parse/image'), [
                    'apikey'     => $apiKey,
                    'language'   => 'eng',
                    'OCREngine'  => '3',
                    'scale'      => 'true',
                    'isTable'    => 'false',
                ]);

            if ($response->failed()) {
                Log::error('[parseReceipt] OCR HTTP failed', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 500),
                ]);
                return response()->json([
                    'success' => false,
                    'error'   => 'OCR service unavailable. Please enter the reference number manually.',
                ], 500);
            }

            $data = $response->json();

            if (!empty($data['IsErroredOnProcessing'])) {
                Log::error('[parseReceipt] OCR processing error', [
                    'errors' => $data['ErrorMessage'] ?? 'Unknown',
                ]);
                return response()->json([
                    'success' => false,
                    'error'   => 'OCR could not read this image. Please enter the reference number manually.',
                ], 500);
            }

            $text = $data['ParsedResults'][0]['ParsedText'] ?? '';
            Log::info('[parseReceipt] OCR raw text', ['text' => $text]);

            $referenceNumber = null;

            if (preg_match('/(?:Ref(?:erence)?\s*(?:No\.?|Number)?[:\s]+)(\d[\d\s\-]{7,18}\d)/i', $text, $m)) {
                $referenceNumber = preg_replace('/[\s\-]+/', '', $m[1]);
            }

            if (!$referenceNumber && preg_match('/\b(\d{9,13})\b/', $text, $m)) {
                $referenceNumber = $m[1];
            }

            return response()->json([
                'success'      => true,
                'reference_no' => $referenceNumber,
                'raw_text'     => $text,
            ]);
        } catch (\Throwable $e) {
            Log::error('[parseReceipt] Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to process receipt. Please enter the reference number manually.',
            ], 500);
        }
    }
}
