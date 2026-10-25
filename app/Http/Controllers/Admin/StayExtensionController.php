<?php
// app/Http/Controllers/Admin/StayExtensionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StayExtensionController extends Controller
{
    /**
     * Display the stay extension requests list.
     */
    public function index()
    {
        $requests = StayExtensionRequest::with(['tenant.user', 'room'])
            ->latest()
            ->get();

        $metrics = [
            'total'    => StayExtensionRequest::count(),
            'pending'  => StayExtensionRequest::pending()->count(),
            'approved' => StayExtensionRequest::approved()->count(),
            'rejected' => StayExtensionRequest::rejected()->count(),
        ];

        $requestsForReact = $requests->mapWithKeys(function ($req) {
            return [$req->id => $req->toReactArray()];
        })->toArray();

        return view('admin.services.stay-extension', compact('requests', 'metrics', 'requestsForReact'));
    }

    /**
     * Store a newly created stay extension request (admin-side).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'           => ['required', 'exists:tenants,id'],
            'room_id'             => ['nullable', 'exists:rooms,id'],
            'current_lease_end'   => ['required', 'date'],
            'requested_lease_end' => ['required', 'date', 'after:current_lease_end'],
            'reason'              => ['required', 'string'],
        ]);

        $extensionDays = StayExtensionRequest::calculateExtensionDays(
            $validated['current_lease_end'],
            $validated['requested_lease_end']
        );

        $stayExtension = StayExtensionRequest::create([
            ...$validated,
            'reference_no'   => StayExtensionRequest::generateReferenceNo(),
            'extension_days' => $extensionDays,
            'status'         => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request created successfully.',
            'data'    => $stayExtension->load('tenant.user'),
        ]);
    }

    public function show(StayExtensionRequest $stayExtensionRequest)
    {
        $stayExtensionRequest->load(['tenant.user', 'room']);

        return response()->json([
            'success' => true,
            'data'    => $stayExtensionRequest->toReactArray(),
        ]);
    }

    public function update(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
            'status'      => ['sometimes', Rule::in(['Pending', 'Approved', 'Rejected'])],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['Approved', 'Rejected'])) {
            $validated['processed_by'] = $request->user()->id;
            $validated['processed_at'] = now();
        }

        $stayExtensionRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request updated successfully.',
            'data'    => $stayExtensionRequest->fresh()->load('tenant.user'),
        ]);
    }

    /**
     * Approve a stay extension request.
     *
     *  1. Extend the tenant's approved reservation check_out.
     *  2. Create an invoice for the extra days at the tenant's room rate.
     */
    public function approve(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        // Guard: already processed
        if ($stayExtensionRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'This request has already been processed.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $tenant      = $stayExtensionRequest->tenant;
            $reservation = $tenant?->approvedReservation;

            // ── 1. Extend the source reservation's check_out ──
            if ($reservation) {
                $reservation->update([
                    'check_out' => $stayExtensionRequest->requested_lease_end,
                ]);
            }

            // ── 2. Create a billing invoice for the extra days ──
            $invoice = $this->createExtensionInvoice($stayExtensionRequest, $reservation);

            // ── 3. Mark the request approved ──
            $stayExtensionRequest->update([
                'status'       => 'Approved',
                'admin_notes'  => $validated['admin_notes'] ?? $stayExtensionRequest->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stay extension approved.'
                    . ($invoice ? ' Extension invoice generated.' : ''),
                'data'    => $stayExtensionRequest->fresh(),
                'invoice' => $invoice,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Failed to approve stay extension: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $stayExtensionRequest->update([
            'status'       => 'Rejected',
            'admin_notes'  => $validated['admin_notes'] ?? $stayExtensionRequest->admin_notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request rejected.',
            'data'    => $stayExtensionRequest->fresh(),
        ]);
    }

    public function destroy(StayExtensionRequest $stayExtensionRequest)
    {
        $stayExtensionRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request deleted successfully.',
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Create an invoice for the extension days at the tenant's room rate.
     * Idempotent — returns existing invoice if one already exists for this ref.
     */
    private function createExtensionInvoice(
        StayExtensionRequest $request,
        ?\App\Models\Reservation $reservation
    ): ?Invoice {
        $tenant = $request->tenant;

        if (!$tenant) {
            return null;
        }

        // Idempotency: skip if an extension invoice for this reference already exists
        $existing = Invoice::where('description', 'like', '%' . $request->reference_no . '%')
            ->where('description', 'like', '%Extension%')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Rate: tenant's rent rate, falling back to the room's daily_rent
        $room = $tenant->room;
        $ratePerDay = (float) ($tenant->rent_rate ?: ($room?->daily_rent ?? 0));

        // Fall back to the reservation's rate if still zero
        if ($ratePerDay <= 0 && $reservation && $reservation->room) {
            $ratePerDay = (float) $reservation->room->daily_rent;
        }

        $days         = max(1, (int) $request->extension_days);
        $totalAmount  = round($ratePerDay * $days, 2);

        // Invoice dates
        $checkIn  = $request->current_lease_end ?? now();
        $checkOut = $request->requested_lease_end ?? now()->addDays($days);

        $roomNumber = $room?->room_number
            ?? $reservation?->desired_room
            ?? 'N/A';

        $invoice = Invoice::create([
            'transaction_no' => Invoice::generateTransactionNo(),
            'tenant_id'      => $tenant->id,
            'room_id'        => $tenant->room_id,
            'tenant_name'    => $tenant->user->name ?? 'Unknown',
            'room_number'    => $roomNumber,
            'description'    => 'Stay Extension Fee — +' . $days . ' day'
                . ($days === 1 ? '' : 's')
                . ' (Ref: ' . $request->reference_no . ')',
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'rate_per_day'   => $ratePerDay,
            'days'           => $days,
            'total_amount'   => $totalAmount,
            'amount_paid'    => 0,
            'balance'        => $totalAmount,
            'due_date'       => now()->addDays(7)->toDateString(),
            'payment_method' => 'Not Set',
            'status'         => 'Unpaid',
        ]);

        return $invoice;
    }
}