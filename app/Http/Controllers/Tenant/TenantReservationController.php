<?php
// app/Http/Controllers/Tenant/TenantReservationController.php

namespace App\Http\Controllers\Tenant;

use App\Support\InputRules;
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
        return app(\App\Services\RoomCatalogService::class)->currentRoomId($tenant);
    }

    /** Room cards are built by the shared catalog so the dashboard browser matches this page. */
    private function buildRoomCards($myReservations, ?int $currentRoomId = null)
    {
        return app(\App\Services\RoomCatalogService::class)->roomCards(collect($myReservations), $currentRoomId);
    }

    private function buildBedSlots(Room $room): array
    {
        return app(\App\Services\RoomCatalogService::class)->bedSlots($room);
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
            'room_id'         => ['required', 'integer', 'exists:rooms,id'],
            'check_in_date'   => ['required', 'date', 'after_or_equal:today', 'before:+1 year'],
            'check_in_time'   => InputRules::time(),
            'check_out_date'  => ['required', 'date', 'after:check_in_date', 'before:+3 years'],
            'check_out_time'  => InputRules::time(),
            'has_companion'   => ['nullable', 'boolean'],
            'notes'           => InputRules::text(InputRules::NOTES, false),
            'payment_option'  => ['required', 'in:half,full'],
            'reference_no'    => InputRules::reference(),
            'receipt'         => InputRules::image(true),
            'room_bed_id'     => InputRules::text(InputRules::ROOM_LABEL, false),
            'agreed_to_terms' => ['accepted'],
            'num_occupants'   => ['nullable', 'integer', 'min:1', 'max:' . InputRules::MAX_OCCUPANTS],
            'companion_names' => InputRules::text(InputRules::COMPANIONS, false),
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
        $request->validate(['receipt' => InputRules::image(true)]);

        $result = app(\App\Services\ReceiptOcrService::class)->scan($request->file('receipt'));

        // 200 even when the scan fails: the tenant just types the reference instead
        return response()->json($result);
    }
}
