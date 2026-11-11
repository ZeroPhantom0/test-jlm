<?php
// app/Http/Controllers/Tenant/TenantStayExtensionController.php

namespace App\Http\Controllers\Tenant;

use App\Events\ServiceRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use App\Models\Reservation;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TenantStayExtensionController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        // ── APPROVED RESERVATION CHECK ──
        $hasApprovedReservation = $tenant
            ? Reservation::where('user_id', $user->id)
                ->where('status', 'Approved')
                ->when($tenant->room_id, fn ($q) => $q->where('room_id', $tenant->room_id))
                ->exists()
            : false;

        $currentCheckOut = $tenant?->approvedReservation?->check_out;
        $canRequestExtension = $hasApprovedReservation && (bool) $currentCheckOut;

        $currentLeaseEnd = $currentCheckOut
            ? $currentCheckOut->format('M j, Y')
            : 'Open-ended';

        $minRequestedDate = $currentCheckOut
            ? $currentCheckOut->copy()->addDay()->toDateString()
            : now()->toDateString();

        // Daily rent for the billing preview
        $dailyRent = (float) ($tenant?->rent_rate ?: ($tenant?->room?->daily_rent ?? 0));

        $requests = StayExtensionRequest::with('room')
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        $roomNumber = $tenant?->room?->room_number ?? '—';

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            // Fetch the associated invoice to get payment info
            $invoice = Invoice::where('description', 'like', '%' . $r->reference_no . '%')->first();

            return [
                $r->id => [
                    'id'                  => $r->id,
                    'ref'                 => $r->reference_no,
                    'tenant'              => $r->tenant->user->name ?? 'Unknown',
                    'room'                => $r->room?->room_number
                        ? 'Room ' . $r->room->room_number
                        : 'N/A',
                    'current_lease_end'   => $r->current_lease_end?->format('M j, Y') ?? '—',
                    'requested_lease_end' => $r->requested_lease_end?->format('M j, Y') ?? '—',
                    'submitted'           => $r->created_at?->format('M j, Y') ?? '—',
                    'reason'              => $r->reason,
                    'status'              => $r->status,
                    'admin_notes'         => $r->admin_notes ?? '',
                    // Payment details
                    'payment_reference'   => $invoice?->receipt_no,
                    'payment_amount'      => $invoice?->total_amount ?? 0,
                    'payment_proof_url'   => $invoice?->receipt_path 
                        ? asset('storage/' . $invoice->receipt_path) 
                        : null,
                ],
            ];
        })->toArray();

        return view('tenants.services.stay-extension', compact(
            'tenant',
            'roomNumber',
            'currentLeaseEnd',
            'minRequestedDate',
            'requests',
            'requestsForReact',
            'hasApprovedReservation',
            'canRequestExtension',
            'dailyRent'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'You are not yet assigned to a room. Please contact the admin.',
            ], 422);
        }

        // ── APPROVED RESERVATION REQUIRED ──
        $hasApprovedReservation = Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('room_id', $tenant->room_id)
            ->exists();

        if (!$hasApprovedReservation) {
            return response()->json([
                'message' => 'A stay extension can only be requested for an approved reservation. '
                           . 'Your current booking is not yet approved — please wait for admin confirmation '
                           . 'or contact support if you believe this is a mistake.',
            ], 422);
        }

        $currentCheckOut = $tenant->approvedReservation?->check_out;

        if (!$currentCheckOut) {
            return response()->json([
                'message' => 'You have no active lease to extend. Please contact the admin.',
            ], 422);
        }

        $validated = $request->validate([
            'current_lease_end'   => ['required', 'date'],
            'requested_lease_end' => [
                'required',
                'date',
                'after:' . $currentCheckOut->toDateString(),
            ],
            'reason'              => ['required', 'string'],
            'reference_no'        => ['required', 'string', 'max:100'],
            'receipt'             => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ], [
            'requested_lease_end.after' =>
            'The requested lease end must be after your current check-out date ('
                . $currentCheckOut->format('M j, Y') . ').',
        ]);

        $extensionDays = StayExtensionRequest::calculateExtensionDays(
            $currentCheckOut->toDateString(),
            $validated['requested_lease_end']
        );

        if ($extensionDays <= 0) {
            return response()->json([
                'message' => 'The requested lease end must be after your current check-out date.',
            ], 422);
        }

        // 1. Save the uploaded receipt
        $receiptPath = $request->file('receipt')->store('reservations/' . $user->id, 'public');

        // 2. Calculate the Bill (Nights x Rate)
        $ratePerDay = (float) ($tenant->rent_rate ?: ($tenant->room?->daily_rent ?? 0));
        $totalAmount = $extensionDays * $ratePerDay;

        DB::beginTransaction();
        try {
            $stayExtension = StayExtensionRequest::create([
                'reference_no'        => StayExtensionRequest::generateReferenceNo(),
                'tenant_id'           => $tenant->id,
                'room_id'             => $tenant->room_id,
                'current_lease_end'   => $currentCheckOut->toDateString(),
                'requested_lease_end' => $validated['requested_lease_end'],
                'extension_days'      => $extensionDays,
                'reason'              => $validated['reason'],
                'status'              => 'Pending',
            ]);

            // 3. Create the Pending Invoice
            Invoice::create([
                'transaction_no' => Invoice::generateTransactionNo(),
                'tenant_id'      => $tenant->id,
                'room_id'        => $tenant->room_id,
                'tenant_name'    => $user->name,
                'room_number'    => $tenant->room?->room_number ?? 'N/A',
                // Ensure #SE- is in the description
                'description'    => 'Stay Extension Fee — +' . $extensionDays . ' day' . ($extensionDays === 1 ? '' : 's') . ' (Ref: #SE-' . $stayExtension->id . ')',
                'check_in'       => $currentCheckOut,
                'check_out'      => $validated['requested_lease_end'],
                'rate_per_day'   => $ratePerDay,
                'days'           => $extensionDays,
                'total_amount'   => $totalAmount,
                'amount_paid'    => $totalAmount, // Mark as paid so it shows in the table
                'balance'        => 0,
                'due_date'       => now()->toDateString(),
                'payment_method' => 'GCash',
                'status'         => 'Pending', // Pending Admin Verification
                'receipt_no'     => $validated['reference_no'],
                'receipt_path'   => $receiptPath,
                'date_paid'      => now()->toDateString(),
            ]);

            DB::commit();

            // Notify admin bell — new request
            event(new ServiceRequestUpdated('stay_extension', 'created', $stayExtension->id));

            return response()->json([
                'success' => true,
                'message' => 'Stay extension request submitted successfully.',
                'data'    => $stayExtension,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            if (Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            report($e);
            return response()->json([
                'message' => 'Failed to submit request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function data()
    {
        $user = Auth::user();
        $tenant = Tenant::with(['room', 'approvedReservation'])->where('user_id', $user->id)->first();

        $requests = StayExtensionRequest::with('room')->where('tenant_id', $tenant?->id)->latest()->get();

        $requestsForReact = $requests->mapWithKeys(function ($r) {
            $invoice = Invoice::where('description', 'like', '%' . $r->reference_no . '%')->first();

            return [
                $r->id => [
                    'id' => $r->id,
                    'ref' => $r->reference_no,
                    'tenant' => $r->tenant->user->name ?? 'Unknown',
                    'room' => $r->room?->room_number ? 'Room ' . $r->room->room_number : 'N/A',
                    'current_lease_end' => $r->current_lease_end?->format('M j, Y') ?? '—',
                    'requested_lease_end' => $r->requested_lease_end?->format('M j, Y') ?? '—',
                    'submitted' => $r->created_at?->format('M j, Y') ?? '—',
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'admin_notes' => $r->admin_notes ?? '',
                    'payment_reference' => $invoice?->receipt_no,
                    'payment_amount'    => $invoice?->total_amount ?? 0,
                    'payment_proof_url' => $invoice?->receipt_path 
                        ? asset('storage/' . $invoice->receipt_path) 
                        : null,
                ],
            ];
        })->toArray();

        $rows = $requests->map(function ($r) {
            return [
                'id' => $r->id,
                'tenant_name' => $r->tenant->user->name ?? 'Unknown',
                'room_number' => $r->room?->room_number ? 'Room ' . $r->room->room_number : 'N/A',
                'current_lease_end' => $r->current_lease_end?->format('M j, Y') ?? '—',
                'requested_lease_end' => $r->requested_lease_end?->format('M j, Y') ?? '—',
                'is_pending' => $r->status === 'Pending',
            ];
        })->values();

        $hasApprovedReservation = $tenant
            ? Reservation::where('user_id', $user->id)
                ->where('status', 'Approved')
                ->when($tenant->room_id, fn ($q) => $q->where('room_id', $tenant->room_id))
                ->exists()
            : false;

        return response()->json([
            'rows' => $rows,
            'react' => $requestsForReact,
            'has_tenant' => (bool) $tenant,
            'has_approved_reservation' => $hasApprovedReservation,
        ]);
    }

    public function cancel(StayExtensionRequest $stayExtensionRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $ownsIt = $stayExtensionRequest->tenant?->user_id === $user->id;
        if (!$ownsIt) {
            abort(403, 'You can only cancel your own requests.');
        }

        if ($stayExtensionRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $stayExtensionRequest->update([
            'status' => 'Rejected',
        ]);

        // Notify admin bell — tenant cancelled stay extension
        event(new ServiceRequestUpdated('stay_extension', 'cancelled', $stayExtensionRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request cancelled.',
        ]);
    }
}