<?php
// app/Models/Invoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'transaction_no',
        'tenant_id',
        'room_id',
        'tenant_name',
        'room_number',
        'description',
        'check_in',
        'check_out',
        'rate_per_day',
        'days',
        'total_amount',
        'amount_paid',
        'balance',
        'due_date',
        'payment_method',
        'status',
        'receipt_no',
        'receipt_path',
        'date_paid',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'check_in'     => 'datetime',
        'check_out'    => 'datetime',
        'due_date'     => 'date',
        'date_paid'    => 'date',
        'verified_at'  => 'datetime',
        'rate_per_day' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid'  => 'decimal:2',
        'balance'      => 'decimal:2',
        'days'         => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path
            ? asset('storage/' . $this->receipt_path)
            : null;
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'Unpaid'  => 'bg-amber-100 text-amber-700',
            'Overdue' => 'bg-rose-100 text-rose-600',
            'Partial' => 'bg-yellow-100 text-yellow-700',
            'Pending' => 'bg-blue-100 text-blue-600',
            'Paid'    => 'bg-green-100 text-green-600',
            default   => 'bg-slate-100 text-slate-500',
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return '₱' . number_format((float) $this->total_amount, 0);
    }

    public function getFormattedPaidAttribute(): string
    {
        return '₱' . number_format((float) $this->amount_paid, 0);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return '₱' . number_format((float) $this->balance, 0);
    }

    public function getFormattedRateAttribute(): string
    {
        return '₱ ' . number_format((float) $this->rate_per_day, 0) . '/day';
    }

    public function getFormattedDaysAttribute(): string
    {
        return $this->days . ' ' . ($this->days === 1 ? 'day' : 'days');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'Unpaid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'Overdue');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('transaction_no', 'like', "%{$search}%")
                      ->orWhere('tenant_name', 'like', "%{$search}%")
                      ->orWhere('room_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['payment_method'] ?? null, function ($q, $method) {
                $q->where('payment_method', $method);
            });
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public static function generateTransactionNo(string $prefix = 'tn'): string
    {
        $lastId = static::max('id') ?? 0;
        return $prefix . '-' . (100 + $lastId + 1);
    }

    public function toReactArray(): array
    {
        return [
            'tn'             => $this->transaction_no,
            'description'    => $this->description,
            'tenant'         => $this->tenant_name,
            'room'           => $this->room_number,
            'total'          => $this->formatted_total,
            'rate'           => $this->formatted_rate,
            'paid'           => $this->formatted_paid,
            'days'           => $this->formatted_days,
            'balance'        => $this->formatted_balance,
            'due'            => $this->due_date?->format('M d, Y') ?? '-',
            'status'         => $this->status,
            'check_in'       => $this->check_in?->format('m-d-Y h:i A') ?? '-',
            'check_out'      => $this->check_out?->format('m-d-Y h:i A') ?? '-',
            'payment_method' => $this->payment_method,
            'receipt_url'    => $this->receipt_url,
        ];
    }
}