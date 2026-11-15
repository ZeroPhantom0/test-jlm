<?php
// app/Http/Controllers/Tenant/TenantDashboardController.php

namespace App\Http\Controllers\Tenant;

use App\Support\InputRules;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use App\Services\TenantDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class TenantDashboardController extends Controller
{
    // -------------------------------------------------------
    // GET /tenants/dashboard
    // -------------------------------------------------------
    public function index(Request $request, TenantDashboardService $dashboard)
    {
        // Bad filter params in a shared/bookmarked URL shouldn't break the
        // dashboard — fall back to defaults instead of redirecting.
        $filters = Validator::make($request->query(), $dashboard->roomFilterRules());

        return view('tenants.dashboard', [
            'user'      => Auth::user(),
            'dashboard' => $dashboard->payload(Auth::user(), $filters->fails() ? [] : $filters->validated()),
        ]);
    }

    // -------------------------------------------------------
    // GET /tenants/dashboard/rooms  (JSON — room browser)
    // -------------------------------------------------------
    public function rooms(Request $request, TenantDashboardService $dashboard)
    {
        $validated = $request->validate($dashboard->roomFilterRules());

        return response()->json($dashboard->roomBrowserPayload($validated));
    }

    // -------------------------------------------------------
    // GET /tenants/dashboard/data  (JSON, for realtime refresh)
    // Room listings have their own endpoint, so they're left out here.
    // -------------------------------------------------------
    public function data(TenantDashboardService $dashboard)
    {
        $payload = $dashboard->payload(Auth::user(), [], false);

        return response()->json($payload);
    }

    // ============================================================
    // PAYMENT PROCESSING (Moved from TenantBillingController)
    // ============================================================

    public function pay(Request $request)
    {
        session()->save();

        $validated = $request->validate([
            'invoice_ref'      => InputRules::reference(),
            // Bank transfer is no longer offered to tenants
            'payment_method'   => ['required', 'in:gcash,counter,GCash,Over The Counter'],
            'reference_number' => InputRules::reference(false),
            'receipt'          => InputRules::image(false),
        ]);

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $method = match ($validated['payment_method']) {
            'gcash', 'GCash'              => 'GCash',
            'counter', 'Over The Counter' => 'Over The Counter',
            default                       => 'GCash',
        };

        DB::beginTransaction();
        try {
            $invoice = Invoice::where('transaction_no', $validated['invoice_ref'])
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['Unpaid', 'Overdue', 'Partial'])
                ->lockForUpdate()
                ->first();

            $reservation = null;
            if (! $invoice) {
                $reservation = Reservation::where('reference_no', $validated['invoice_ref'])
                    ->where('user_id', $user->id)
                    ->where('status', 'Approved')
                    ->where('remaining', '>', 0)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $invoice && ! $reservation) {
                DB::rollBack();
                return response()->json(['message' => 'Invoice or reservation not found, or already paid/pending.'], 404);
            }

            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'public');
            }

            if ($invoice) {
                // The tenant pays the remaining balance. It stays in
                // pending_amount until the admin verifies it — amount_paid
                // only ever holds verified money (no double counting).
                $invoice->update([
                    'payment_method' => $method,
                    'receipt_no'     => $validated['reference_number'] ?? $invoice->receipt_no,
                    'receipt_path'   => $receiptPath ?? $invoice->receipt_path,
                    'status'         => 'Pending',
                    'pending_amount' => (float) $invoice->balance,
                ]);
            } else {
                $invoice = Invoice::create([
                    'transaction_no' => 'INV-' . strtoupper(uniqid()),
                    'tenant_id'      => $tenant->id,
                    'room_id'        => $reservation->room_id,
                    'tenant_name'    => $reservation->tenant_name,
                    'room_number'    => $reservation->desired_room,
                    'description'    => 'Reservation balance – ' . $reservation->reference_no,
                    'total_amount'   => $reservation->remaining,
                    'amount_paid'    => 0,
                    'pending_amount' => $reservation->remaining,
                    'balance'        => $reservation->remaining,
                    'due_date'       => $reservation->check_in,
                    'payment_method' => $method,
                    'receipt_no'     => $validated['reference_number'] ?? null,
                    'receipt_path'   => $receiptPath,
                    'status'         => 'Pending',
                ]);
                $reservation->update([
                    'payment_method'   => $method,
                    'proof_of_payment' => $receiptPath,
                ]);
            }

            DB::commit();

            event(new \App\Events\InvoiceUpdated('submitted', $invoice->id));

            return response()->json([
                'message' => 'Payment submitted. Awaiting admin verification.',
                'invoice' => $invoice->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Failed to submit payment: ' . $e->getMessage()], 500);
        }
    }

    public function cancelPayment(Request $request)
    {
        $validated = $request->validate(['invoice_ref' => InputRules::reference()]);

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $invoice = Invoice::where('transaction_no', $validated['invoice_ref'])
            ->where('tenant_id', $tenant->id)
            ->where('status', 'Pending')
            ->first();

        if (! $invoice) {
            return back()->with('error', 'Pending invoice not found.');
        }

        if ($invoice->receipt_path && Storage::disk('public')->exists($invoice->receipt_path)) {
            Storage::disk('public')->delete($invoice->receipt_path);
        }

        $invoice->update(\App\Services\BillingService::revertSubmission($invoice) + [
            'receipt_path' => null,
            'receipt_no'   => null,
        ]);

        event(new \App\Events\InvoiceUpdated('cancelled', $invoice->id));

        return back()->with('success', 'Submission cancelled. You can pay again.');
    }

    public function parseReceipt(Request $request)
    {
        $request->validate(['receipt' => InputRules::image(true)]);

        $result = app(\App\Services\ReceiptOcrService::class)->scan($request->file('receipt'));

        // 200 even when the scan fails: the tenant just types the reference instead
        return response()->json($result);
    }
}
