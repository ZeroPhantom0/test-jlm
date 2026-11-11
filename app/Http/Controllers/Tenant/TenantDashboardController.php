<?php
// app/Http/Controllers/Tenant/TenantDashboardController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class TenantDashboardController extends Controller
{
    // -------------------------------------------------------
    // GET /tenants/dashboard
    // -------------------------------------------------------
    public function index()
    {
        $user = Auth::user();
        $data = $this->buildDashboardData($user);

        return view('tenants.dashboard', array_merge($data, [
            'user' => $user,
        ]));
    }

    // -------------------------------------------------------
    // GET /tenants/dashboard/data  (JSON, for realtime refresh)
    // -------------------------------------------------------
    public function data()
    {
        $user = Auth::user();
        $data = $this->buildDashboardData($user);

        return response()->json([
            'outstandingBalance' => (float) $data['outstandingBalance'],
            'unpaidCount'        => (int)   $data['unpaidCount'],
            'primaryInvoice'     => $data['primaryInvoice'],
            'paymentPayload'     => $data['paymentPayload'],
            'room'               => $data['room'] ? [
                'room_number'  => $data['room']->room_number,
                'room_type'    => $data['room']->room_type,
                'room_bed_id'  => $data['room']->room_bed_id,
                'floor'        => $data['room']->floor,
                'daily_rent'   => (float) $data['room']->daily_rent,
            ] : null,
            'check_in'           => $data['tenant']?->approvedReservation?->check_in?->format('M d, Y'),
            'check_out'          => $data['tenant']?->approvedReservation?->check_out?->format('M d, Y'),
            'history'            => $data['historyItems']->map(fn ($h) => [
                'key'    => $h['key'],
                'icon'   => $h['icon'],
                'title'  => $h['title'],
                'date'   => $h['date'],
                'status' => $h['status'],
            ])->values(),
        ]);
    }

    // ============================================================
    // PAYMENT PROCESSING (Moved from TenantBillingController)
    // ============================================================

    public function pay(Request $request)
    {
        session()->save();

        $validated = $request->validate([
            'invoice_ref'      => ['required', 'string'],
            'payment_method'   => ['required', 'in:gcash,bank,counter,GCash,Bank Transfer,Over The Counter'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt'          => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp', 'max:10240'],
        ]);

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $method = match ($validated['payment_method']) {
            'gcash', 'GCash'              => 'GCash',
            'bank',  'Bank Transfer'      => 'Bank Transfer',
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
                $newAmountPaid = (float) $invoice->amount_paid + (float) $invoice->balance;

                $invoice->update([
                    'payment_method' => $method,
                    'receipt_no'     => $validated['reference_number'] ?? $invoice->receipt_no,
                    'receipt_path'   => $receiptPath ?? $invoice->receipt_path,
                    'status'         => 'Pending',
                    'amount_paid'    => $newAmountPaid,
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
        $validated = $request->validate(['invoice_ref' => ['required', 'string']]);

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

        $revertedAmount = max(0, (float) $invoice->amount_paid - (float) $invoice->balance);
        $revertStatus   = $revertedAmount > 0 ? 'Partial' : 'Unpaid';

        $invoice->update([
            'status'       => $revertStatus,
            'receipt_path' => null,
            'receipt_no'   => null,
            'amount_paid'  => $revertedAmount,
        ]);

        event(new \App\Events\InvoiceUpdated('cancelled', $invoice->id));

        return back()->with('success', 'Submission cancelled. You can pay again.');
    }

    public function parseReceipt(Request $request)
    {
        $request->validate(['receipt' => 'required|image|max:5120']);

        try {
            $file = $request->file('receipt');
            $apiKey = config('ocr-space.api_key') ?? env('OCR_SPACE_API_KEY');

            if (empty($apiKey)) {
                return response()->json(['success' => false, 'error' => 'OCR service is not configured.'], 500);
            }

            $response = Http::timeout(30)
                ->asMultipart()
                ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
                ->post('https://api.ocr.space/parse/image', [
                    'apikey'     => $apiKey,
                    'language'   => 'eng',
                    'OCREngine'  => '3',
                    'scale'      => 'true',
                    'isTable'    => 'false',
                ]);

            if ($response->failed()) {
                return response()->json(['success' => false, 'error' => 'OCR service unavailable.'], 500);
            }

            $data = $response->json();
            if (!empty($data['IsErroredOnProcessing'])) {
                return response()->json(['success' => false, 'error' => 'OCR could not read image.'], 500);
            }

            $text = $data['ParsedResults'][0]['ParsedText'] ?? '';
            $referenceNumber = null;

            if (preg_match('/(?:Ref(?:erence)?\s*(?:No\.?|Number)?[:\s]+)(\d[\d\s\-]{7,18}\d)/i', $text, $m)) {
                $referenceNumber = preg_replace('/[\s\-]+/', '', $m[1]);
            }
            if (!$referenceNumber && preg_match('/\b(\d{9,13})\b/', $text, $m)) {
                $referenceNumber = $m[1];
            }

            return response()->json(['success' => true, 'reference_no' => $referenceNumber]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to process receipt.'], 500);
        }
    }

    // ============================================================
    // SHARED BUILDER (used by index() and data())
    // ============================================================
    private function buildDashboardData($user): array
    {
        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        $room        = $tenant?->room;
        $reservation = $tenant?->approvedReservation;

        $isExpired = false;
        if ($reservation && $reservation->check_out && $reservation->check_out->isPast()) {
            $isExpired = true;
        }
        if ($isExpired) {
            $room = null;
        }

        $invoiceBalance  = 0.0;
        $invoiceCount    = 0;
        $primaryInvoice  = null;
        $paymentPayload  = null;

        if ($tenant) {
            $rejectedRefs = \App\Models\Reservation::where('user_id', $user->id)
                ->where('status', 'Rejected')
                ->pluck('reference_no')
                ->toArray();

            $unpaidInvoicesQuery = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['Unpaid', 'Partial', 'Overdue']);

            foreach ($rejectedRefs as $ref) {
                $unpaidInvoicesQuery->where('description', 'NOT LIKE', "%{$ref}%");
            }

            $unpaidInvoices = $unpaidInvoicesQuery->orderBy('due_date')->get();

            $invoiceBalance = (float) $unpaidInvoices->sum('balance');
            $invoiceCount   = $unpaidInvoices->count();

            /** @var Invoice|null $first */
            $first = $unpaidInvoices->first();
            if ($first) {
                $primaryInvoice = [
                    'reference'    => $first->transaction_no,
                    'title'        => $first->description,
                    'amount'       => (float) $first->balance,
                    'due_date'     => $first->due_date?->format('M j, Y') ?? '—',
                    'gcash_number' => '0917-123-4567',
                ];

                $paymentPayload = $primaryInvoice;
            }
        }

        $reservationRemaining = 0.0;

        if ($tenant && $reservation && !$isExpired) {
            if ($reservation->status === 'Approved' && (float) $reservation->remaining > 0) {
                $billedRemaining = (float) Invoice::where('tenant_id', $tenant->id)
                    ->whereIn('status', ['Unpaid', 'Partial', 'Overdue', 'Pending'])
                    ->where(function ($q) {
                        $q->where('description', 'like', '%remaining%')
                          ->orWhere('description', 'like', '%reservation%');
                    })
                    ->sum('balance');

                $reservationRemaining = max(0, (float) $reservation->remaining - $billedRemaining);
            }
        }

        $outstandingBalance = $invoiceBalance + $reservationRemaining;
        $unpaidCount        = $invoiceCount + ($reservationRemaining > 0 ? 1 : 0);

        if (!$paymentPayload && $reservationRemaining > 0 && $reservation) {
            $synthetic = [
                'reference'    => 'RES-' . $reservation->id,
                'title'        => 'Reservation Remaining Balance',
                'amount'       => (float) $reservationRemaining,
                'due_date'     => $reservation->check_in?->format('M j, Y') ?? '—',
                'gcash_number' => '0917-123-4567',
            ];
            $paymentPayload = $synthetic;
            $primaryInvoice = $primaryInvoice ?: $synthetic;
        }

        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(5)
            ->get();

        $historyItems = collect();
        if ($tenant) {
            $historyItems = collect()
                ->merge($this->historyReservations($user))
                ->merge($this->historyMaintenance($tenant))
                ->merge($this->historyRoomChanges($tenant))
                ->merge($this->historyStayExtensions($tenant))
                ->reject(fn ($item) => $item['status'] === 'pending')
                ->sortByDesc('timestamp')
                ->take(4)
                ->values();
        }

        $greetingName = explode(' ', trim($user->name))[0] ?: 'Tenant';

        return [
            'tenant'             => $tenant,
            'room'               => $room,
            'outstandingBalance' => $outstandingBalance,
            'unpaidCount'        => $unpaidCount,
            'announcements'      => $announcements,
            'historyItems'       => $historyItems,
            'greetingName'       => $greetingName,
            'primaryInvoice'     => $primaryInvoice,
            'paymentPayload'     => $paymentPayload,
        ];
    }

    // ============================================================
    // HISTORY SOURCES
    // ============================================================

    private function historyReservations($user)
    {
        return Reservation::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('tenant_name', $user->name);
            })
            ->latest()
            ->get()
            ->map(function (Reservation $r) {
                $isPast = $r->status === 'Approved' && $r->check_out && $r->check_out->isPast();

                $status = match (true) {
                    $r->status === 'Approved' && $isPast => 'expired',
                    $r->status === 'Approved'            => 'approved',
                    $r->status === 'Rejected'            => 'rejected',
                    $r->status === 'Expired'             => 'expired',
                    default                              => 'pending',
                };

                return [
                    'key'       => 'rsv-' . $r->id,
                    'icon'      => 'fa-regular fa-calendar-check',
                    'title'     => 'Reservation — ' . $r->desired_room,
                    'date'      => $r->created_at?->format('M d, Y'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'status'    => $status,
                ];
            });
    }

    private function historyMaintenance(Tenant $tenant)
    {
        return MaintenanceRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (MaintenanceRequest $m) {
                $status = match ($m->status) {
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                    default     => 'pending',
                };

                return [
                    'key'       => 'mt-' . $m->id,
                    'icon'      => 'fa-solid fa-wrench',
                    'title'     => $m->title,
                    'date'      => $m->created_at?->format('M d, Y'),
                    'timestamp' => $m->completed_at ?? $m->created_at,
                    'status'    => $status,
                ];
            });
    }

    private function historyRoomChanges(Tenant $tenant)
    {
        return RoomChangeRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (RoomChangeRequest $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default    => 'pending',
                };

                return [
                    'key'       => 'rc-' . $r->id,
                    'icon'      => 'fa-solid fa-house',
                    'title'     => 'Room Change — ' . $r->current_room . ' → ' . $r->requested_room,
                    'date'      => $r->created_at?->format('M d, Y'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'status'    => $status,
                ];
            });
    }

    private function historyStayExtensions(Tenant $tenant)
    {
        return StayExtensionRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (StayExtensionRequest $s) {
                $status = match ($s->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default    => 'pending',
                };

                return [
                    'key'       => 'se-' . $s->id,
                    'icon'      => 'fa-regular fa-calendar-plus',
                    'title'     => 'Stay Extension — +' . $s->extension_days . ' day' . ($s->extension_days === 1 ? '' : 's'),
                    'date'      => $s->created_at?->format('M d, Y'),
                    'timestamp' => $s->processed_at ?? $s->created_at,
                    'status'    => $status,
                ];
            });
    }
}