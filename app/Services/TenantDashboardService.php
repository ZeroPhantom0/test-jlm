<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Support\InputRules;
use Illuminate\Support\Facades\Auth;

/**
 * Builds what the tenant dashboard shows: the hero search, the
 * "Choose your kind of comfort" categories and the room browser.
 * Also builds the outstanding-bills list the Reservations page uses.
 * The controllers stay thin: they only validate input and return view / JSON.
 */
class TenantDashboardService
{
    private const GCASH_NUMBER = '0917-123-4567';

    /** Cover photo + copy for each room category on the dashboard. */
    private const CATEGORIES = [
        'Solo Room' => [
            'title' => 'Solo Rooms',
            'desc'  => 'A private retreat designed for focus, rest, and complete independence.',
            'img'   => '/images/solo-room.jpg',
        ],
        'Barkada Room' => [
            'title' => 'Barkada Rooms',
            'desc'  => 'Generous shared spaces for friends who want to live well together.',
            'img'   => '/images/barkada-room.jpg',
        ],
        'Bedspace' => [
            'title' => 'Bedspace',
            'desc'  => 'Budget-friendly stays with the essentials already considered.',
            'img'   => '/images/bedspace-room.jpg',
        ],
    ];

    /** Dashboard payload: greeting name, room categories and the room browser. */
    public function payload($user, array $roomFilters = []): array
    {
        $rooms = $this->roomBrowserPayload($roomFilters);

        $categories = collect(self::CATEGORIES)->map(fn ($c, $type) => $c + [
            'type'      => $type,
            'available' => (int) ($rooms['facets']['typeCounts'][$type] ?? 0),
            'listed'    => (int) ($rooms['facets']['listedCounts'][$type] ?? 0),
        ])->values()->all();

        return [
            'user' => [
                'firstName' => explode(' ', trim($user->name))[0] ?: 'there',
            ],
            'hasRoom'    => $rooms['hasRoom'],
            'categories' => $categories,
            'links'      => [
                'houseRules' => '/tenants/announcements',
            ],
            'rooms' => $rooms,
        ];
    }

    // ============================================================
    // ROOM BROWSER
    // ============================================================

    private const ROOMS_PER_PAGE = 12;
    public const ROOM_TYPES = ['Solo Room', 'Bedspace', 'Barkada Room'];
    private const ROOM_SORTS = ['recommended', 'price_asc', 'price_desc', 'beds', 'newest'];
    /** Availability filter → room status (every listed room still has a free bed). */
    private const ROOM_AVAILABILITY = ['available' => 'Available', 'reserved' => 'Reserved', 'occupied' => 'Occupied'];

    public function roomFilterRules(): array
    {
        return [
            'search'       => InputRules::search(),
            'min_price'    => ['nullable', 'numeric', 'min:0', 'max:' . InputRules::MONEY_MAX],
            'max_price'    => ['nullable', 'numeric', 'min:0', 'max:' . InputRules::MONEY_MAX],
            'type'         => ['nullable', 'array', 'max:5'],
            'type.*'       => ['string', 'in:Solo Room,Bedspace,Barkada Room'],
            'floor'        => ['nullable', 'integer', 'min:1', 'max:' . InputRules::MAX_FLOORS],
            'sort'         => ['nullable', 'in:' . implode(',', self::ROOM_SORTS)],
            'availability' => ['nullable', 'in:' . implode(',', array_keys(self::ROOM_AVAILABILITY))],
            'page'         => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Everything the dashboard room browser needs: the filtered, sorted,
     * paginated list of available rooms plus the top recommendations.
     * Availability is computed per room (bed slots, live stays), so the
     * filtering/paging runs over the computed cards rather than in SQL.
     */
    public function roomBrowserPayload(array $f): array
    {
        $user    = Auth::user();
        $tenant  = Tenant::with(['room', 'approvedReservation'])->where('user_id', $user->id)->first();
        $catalog = app(\App\Services\RoomCatalogService::class);

        $mine          = $catalog->reservationsFor($user)->load('room');
        $currentRoomId = $catalog->currentRoomId($tenant);

        // Every listed room; "available" = at least one free bed right now
        $listed    = $catalog->roomCards($mine, $currentRoomId);
        $available = $listed->filter(fn ($c) => $c['is_available'])->values();

        $scoring = app(\App\Services\RoomRecommendationService::class)
            ->recommend($available, $mine, $tenant, null);
        $scores = collect($scoring['rooms'])->pluck('matchScore', 'id');

        // ── Filters ──
        $search = mb_strtolower(trim((string) ($f['search'] ?? '')));
        $min    = isset($f['min_price']) ? (float) $f['min_price'] : null;
        $max    = isset($f['max_price']) ? (float) $f['max_price'] : null;
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }
        $types  = array_values(array_filter((array) ($f['type'] ?? [])));
        $floor  = isset($f['floor']) ? (int) $f['floor'] : null;
        $sort   = $f['sort'] ?? 'recommended';
        $availability = $f['availability'] ?? null;
        $status = $availability ? self::ROOM_AVAILABILITY[$availability] : null;

        $filtered = $available->filter(function ($c) use ($search, $min, $max, $types, $floor, $status) {
            if ($search !== '' && ! str_contains(mb_strtolower($c['num'] . ' ' . $c['name']), $search)) return false;
            if ($min !== null && $c['price'] < $min) return false;
            if ($max !== null && $c['price'] > $max) return false;
            if ($types && ! in_array($c['name'], $types, true)) return false;
            if ($floor !== null && (int) $c['floor'] !== $floor) return false;
            if ($status !== null && $c['status'] !== $status) return false;
            return true;
        })->map(fn ($c) => $c + ['matchScore' => $scores[$c['id']] ?? null]);

        $sorted = match ($sort) {
            'price_asc'  => $filtered->sortBy([['price', 'asc'], ['num', 'asc']]),
            'price_desc' => $filtered->sortBy([['price', 'desc'], ['num', 'asc']]),
            'beds'       => $filtered->sortBy([['empty_beds', 'desc'], ['price', 'asc']]),
            'newest'     => $filtered->sortByDesc('created_at'),
            default      => $filtered->sortBy([
                fn ($a, $b) => ($b['matchScore'] ?? -1) <=> ($a['matchScore'] ?? -1),
                ['price', 'asc'],
            ]),
        };

        $total    = $sorted->count();
        $lastPage = max(1, (int) ceil($total / self::ROOMS_PER_PAGE));
        $page     = min(max(1, (int) ($f['page'] ?? 1)), $lastPage);

        $prices = $available->pluck('price');

        // Same rule as the Room Change page: an Approved reservation for the current room
        $canChangeRoom = $currentRoomId !== null && Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('room_id', $tenant->room_id)
            ->exists();
        $servicesLocked = $canChangeRoom && Tenant::hasUnsettledBalance($user->id);

        return [
            'rooms'       => $sorted->forPage($page, self::ROOMS_PER_PAGE)->values()->all(),
            'pagination'  => [
                'page'     => $page,
                'lastPage' => $lastPage,
                'perPage'  => self::ROOMS_PER_PAGE,
                'total'    => $total,
            ],
            'recommended'    => array_slice($scoring['rooms'], 0, 4),
            'totalAvailable' => $available->count(),
            'openBeds'       => (int) $available->sum('empty_beds'),
            'filters'        => [
                'search'       => $f['search'] ?? '',
                'min_price'    => $min,
                'max_price'    => $max,
                'type'         => $types,
                'floor'        => $floor,
                'sort'         => $sort,
                'availability' => $availability,
            ],
            'facets' => [
                // All three room types are always filterable, with live counts
                'types'      => self::ROOM_TYPES,
                'typeCounts' => collect(self::ROOM_TYPES)
                    ->mapWithKeys(fn ($t) => [$t => $available->where('name', $t)->count()])
                    ->all(),
                'listedCounts' => collect(self::ROOM_TYPES)
                    ->mapWithKeys(fn ($t) => [$t => $listed->where('name', $t)->count()])
                    ->all(),
                'floors'   => $available->pluck('floor')->unique()->sort()->values()->all(),
                'priceMin' => $prices->isEmpty() ? 0 : (float) $prices->min(),
                'priceMax' => $prices->isEmpty() ? 0 : (float) $prices->max(),
                // Rooms have no amenities column yet → no amenity filter/icons
                'amenities' => [],
            ],
            'hasRoom'    => $currentRoomId !== null,
            'currentRoom' => $currentRoomId ? [
                'num'  => $tenant->room?->room_number,
                'name' => $tenant->room?->room_type,
            ] : null,
            'roomChange' => [
                'allowed' => $canChangeRoom && ! $servicesLocked,
                'locked'  => $servicesLocked,
                'url'     => '/tenants/services/room-change',
            ],
            'factors' => $scoring['factors'],
        ];
    }

    // ============================================================
    // OUTSTANDING BILLS (Reservations page → "My Reservations")
    // ============================================================

    /**
     * Every bill the tenant still has to settle, oldest due first:
     *   - invoices that are Unpaid / Partial / Overdue (payable now)
     *   - invoices whose payment is Pending admin verification (not payable)
     *   - the remaining balance of an approved stay not invoiced yet
     *
     * Each row carries `payNow`: the payload the Pay Now modal expects
     * (null while a payment is awaiting verification).
     */
    public function outstandingBills($user): array
    {
        $tenant = Tenant::with('approvedReservation')->where('user_id', $user->id)->first();
        if (! $tenant) {
            return [];
        }

        // Bills for reservations that were rejected / cancelled are not owed
        $rejectedRefs = Reservation::where('user_id', $user->id)
            ->whereIn('status', ['Rejected', 'Cancelled'])
            ->pluck('reference_no')
            ->filter()
            ->all();

        $query = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue', 'Pending']);
        foreach ($rejectedRefs as $ref) {
            $query->where('description', 'NOT LIKE', "%{$ref}%");
        }
        $invoices = $query->orderBy('due_date')->get();

        $today = now('Asia/Manila')->startOfDay();

        $rows = $invoices->map(function (Invoice $inv) use ($today) {
            $pending = $inv->status === 'Pending';
            $overdue = $inv->status === 'Overdue'
                || (! $pending && $inv->due_date && $inv->due_date->copy()->startOfDay()->lt($today));
            $status = match (true) {
                $pending                       => 'Pending',
                $overdue                       => 'Overdue',
                (float) $inv->amount_paid > 0  => 'Partial',
                default                        => $inv->status,
            };
            $balance = (float) $inv->balance;

            return [
                'key'     => 'inv-' . $inv->id,
                'title'   => $inv->description ?: 'Bill',
                'room'    => $inv->room_number,
                'status'  => $status,
                'total'   => (float) $inv->total_amount,
                'paid'    => (float) $inv->amount_paid,
                'balance' => $pending ? (float) ($inv->pending_amount ?: $balance) : $balance,
                'due'     => $inv->due_date?->format('M j, Y') ?? '—',
                'payNow'  => $pending || $balance <= 0.005 ? null : [
                    'reference'    => $inv->transaction_no,
                    'title'        => $inv->description,
                    'amount'       => $balance,
                    'due_date'     => $inv->due_date?->format('M j, Y') ?? '—',
                    'gcash_number' => self::GCASH_NUMBER,
                ],
            ];
        })->filter(fn ($r) => $r['balance'] > 0.005)->values();

        // Remaining balance of the approved stay that has no invoice yet
        $stay = $tenant->approvedReservation;
        if ($stay && $stay->status === 'Approved' && ! $stay->isStayOver() && (float) $stay->remaining > 0) {
            $billed = (float) $invoices
                ->filter(fn ($i) => stripos((string) $i->description, 'remaining') !== false
                    || stripos((string) $i->description, 'reservation') !== false)
                ->sum('balance');
            $left = max(0, (float) $stay->remaining - $billed);

            if ($left > 0.005) {
                $paid = (float) $stay->reservation_fee;
                $rows->push([
                    'key'     => 'rsv-balance-' . $stay->id,
                    'title'   => 'Reservation Down Payment',
                    'room'    => $stay->desired_room,
                    'status'  => $paid > 0 ? 'Partial' : 'Unpaid',
                    'total'   => (float) $stay->total_charges,
                    'paid'    => $paid,
                    'balance' => $left,
                    'due'     => $stay->check_in?->format('M j, Y') ?? '—',
                    // pay() looks the reservation up by its reference number
                    'payNow'  => [
                        'reference'    => $stay->reference_no,
                        'title'        => 'Reservation remaining balance',
                        'amount'       => $left,
                        'due_date'     => $stay->check_in?->format('M j, Y') ?? '—',
                        'gcash_number' => self::GCASH_NUMBER,
                    ],
                ]);
            }
        }

        return $rows->values()->all();
    }
}
