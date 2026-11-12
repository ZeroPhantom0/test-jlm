<?php
// app/Http/Controllers/Admin/StayExtensionController.php

namespace App\Http\Controllers\Admin;

use App\Events\InvoiceUpdated;
use App\Events\ReservationUpdated;
use App\Events\ServiceRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Models\StayExtensionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StayExtensionController extends Controller
{
    public function index()
    {
        $requests = StayExtensionRequest::with(['tenant.user', 'room'])
            ->latest()
            ->get();

        $metrics = $this->buildMetrics();

        // ✅ Build from the SAME serializer used by data(), keyed by real id
        $requestsForReact = $this->buildReactMap($requests);

        return view('admin.services.stay-extension', compact('requests', 'metrics', 'requestsForReact'));
    }

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

        event(new ServiceRequestUpdated('stay_extension', 'created', $stayExtension->id));

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
            'data'    => $this->serializeForReact($stayExtensionRequest),
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

        event(new ServiceRequestUpdated('stay_extension', 'updated', $stayExtensionRequest->id));

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request updated successfully.',
            'data'    => $stayExtensionRequest->fresh()->load('tenant.user'),
        ]);
    }

    public function approve(Request $request, StayExtensionRequest $stayExtensionRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        if ($stayExtensionRequest->status !== 'Pending') {
            return response()->json([
                'message' => 'This request has already been processed.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $tenant      = $stayExtensionRequest->tenant;
            $reservation = $tenant?->approvedReservation;

            if ($reservation) {
                $reservation->update([
                    'check_out' => $stayExtensionRequest->requested_lease_end,
                ]);
            }

            $invoice = $this->createExtensionInvoice($stayExtensionRequest, $reservation);

            $stayExtensionRequest->update([
                'status'       => 'Approved',
                'admin_notes'  => $validated['admin_notes'] ?? $stayExtensionRequest->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            DB::commit();

            if ($invoice) {
                event(new InvoiceUpdated('created', $invoice->id));
            }
            event(new ReservationUpdated('updated', $reservation?->id));
            event(new ServiceRequestUpdated('stay_extension', 'approved', $stayExtensionRequest->id));

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

        $voidedInvoiceIds = DB::transaction(function () use ($stayExtensionRequest, $validated, $request) {
            $stayExtensionRequest->update([
                'status'       => 'Rejected',
                'admin_notes'  => $validated['admin_notes'] ?? $stayExtensionRequest->admin_notes,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            // Drop the unverified fee invoice so it leaves Billing too
            return BillingService::voidServiceRequestInvoices($stayExtensionRequest->reference_no);
        });

        event(new ServiceRequestUpdated('stay_extension', 'rejected', $stayExtensionRequest->id));
        BillingService::broadcastInvoiceChange('rejected', $voidedInvoiceIds);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request rejected.',
            'data'    => $stayExtensionRequest->fresh(),
        ]);
    }

    public function data()
    {
        $requests = StayExtensionRequest::with(['tenant.user', 'room'])->latest()->get();

        // ✅ Row shape matches what admin blade uses; `react` is keyed by real id
        $rows = $requests->map(fn ($req) => $this->serializeRow($req))->values();

        return response()->json([
            'rows'    => $rows,
            'metrics' => $this->buildMetrics(),
            'react'   => $this->buildReactMap($requests),
        ]);
    }

    public function destroy(StayExtensionRequest $stayExtensionRequest)
    {
        $id = $stayExtensionRequest->id;

        $voidedInvoiceIds = DB::transaction(function () use ($stayExtensionRequest) {
            $ids = BillingService::voidServiceRequestInvoices($stayExtensionRequest->reference_no);
            $stayExtensionRequest->delete();
            return $ids;
        });

        event(new ServiceRequestUpdated('stay_extension', 'deleted', $id));
        BillingService::broadcastInvoiceChange('deleted', $voidedInvoiceIds);

        return response()->json([
            'success' => true,
            'message' => 'Stay extension request deleted successfully.',
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function buildMetrics(): array
    {
        return [
            'total'    => StayExtensionRequest::count(),
            'pending'  => StayExtensionRequest::pending()->count(),
            'approved' => StayExtensionRequest::approved()->count(),
            'rejected' => StayExtensionRequest::rejected()->count(),
        ];
    }

    /**
     * The exact row shape used for the table refresh AND the React modal payload.
     * Includes `id` and keys the modal needs (tenant, reference_no, current_end, etc.).
     */
    private function serializeRow(StayExtensionRequest $req): array
    {
        return [
            'id'                  => $req->id,
            'tenant_name'         => $req->tenant?->user?->name ?? 'Unknown',
            'room_number'         => $req->room?->room_number ? 'Room ' . $req->room->room_number : 'N/A',
            'current_lease_end'   => $req->current_lease_end?->format('M d, Y'),
            'requested_lease_end' => $req->requested_lease_end?->format('M d, Y'),
            'extension_label'     => $req->extension_label,
            'status'              => $req->status,
            'status_class'        => $req->status_class,
            'is_pending'          => $req->status === 'Pending',
        ];
    }

    /**
     * Full payload consumed by React modals. Mirrors admin/service keys used in JSX.
     */
    private function serializeForReact(StayExtensionRequest $req): array
    {
        return [
            'id'              => $req->id,
            'tenant'          => $req->tenant?->user?->name ?? 'Unknown',
            'reference_no'    => $req->reference_no,
            'room_number'     => $req->room?->room_number ? 'Room ' . $req->room->room_number : 'N/A',
            'current_end'     => $req->current_lease_end?->format('M d, Y'),
            'requested_end'   => $req->requested_lease_end?->format('M d, Y'),
            'extension_days'  => $req->extension_label,
            'status'          => $req->status,
            'reason'          => $req->reason,
            'admin_notes'     => $req->admin_notes ?? '',
            'submitted'       => $req->created_at?->format('M d, Y'),
        ];
    }

    /**
     * Map keyed by real primary key.
     */
    private function buildReactMap($requests): array
    {
        return $requests
            ->mapWithKeys(fn ($req) => [
                (string) $req->id => $this->serializeForReact($req),
            ])
            ->all();
    }

    private function createExtensionInvoice(
        StayExtensionRequest $request,
        ?\App\Models\Reservation $reservation
    ): ?Invoice {
        $tenant = $request->tenant;

        if (!$tenant) {
            return null;
        }

        $existing = Invoice::where('description', 'like', '%' . $request->reference_no . '%')
            ->where('description', 'like', '%Extension%')
            ->first();

        if ($existing) {
            return $existing;
        }

        $room       = $tenant->room;
        $ratePerDay = (float) ($tenant->rent_rate ?: ($room?->daily_rent ?? 0));

        if ($ratePerDay <= 0 && $reservation && $reservation->room) {
            $ratePerDay = (float) $reservation->room->daily_rent;
        }

        $days        = max(1, (int) $request->extension_days);
        $totalAmount = round($ratePerDay * $days, 2);

        $checkIn  = $request->current_lease_end ?? now();
        $checkOut = $request->requested_lease_end ?? now()->addDays($days);

        $roomNumber = $room?->room_number
            ?? $reservation?->desired_room
            ?? 'N/A';

        return Invoice::create([
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
    }
}