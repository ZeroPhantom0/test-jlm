<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use App\Support\InputRules;
use Illuminate\Support\Facades\Auth;

/**
 * Builds everything the tenant dashboard shows. The controller stays thin:
 * it only validates input and returns view / JSON.
 */
class TenantDashboardService
{
    public const STATE_EXPLORER = 'explorer';
    public const STATE_PENDING  = 'pending';
    public const STATE_RESIDENT = 'resident';

    private const TZ = 'Asia/Manila';
    private const DUE_SOON_DAYS = 3;
    private const NEW_ANNOUNCEMENT_HOURS = 72;

    /**
     * Full dashboard payload.
     *
     * tenantState:
     *   resident — lives in a room now (or has an approved upcoming stay)
     *   pending  — has a reservation waiting for admin approval
     *   explorer — neither
     *
     * Room browser data is only built when it's needed (explorer / pending),
     * or when $withRooms is forced. Residents load it on demand.
     */
    public function payload($user, array $roomFilters = [], ?bool $withRooms = null): array
    {
        $now     = now(self::TZ);
        $legacy  = $this->buildDashboardData($user);
        $catalog = app(RoomCatalogService::class);

        /** @var Tenant|null $tenant */
        $tenant = Tenant::with(['room.virtualTour', 'approvedReservation'])->where('user_id', $user->id)->first();
        $currentRoomId = $catalog->currentRoomId($tenant);

        $pending = Reservation::with(['room.virtualTour'])
            ->where('user_id', $user->id)
            ->where('status', 'Pending')
            ->latest()
            ->first();

        $state = $currentRoomId
            ? self::STATE_RESIDENT
            : ($pending ? self::STATE_PENDING : self::STATE_EXPLORER);

        $withRooms ??= $state !== self::STATE_RESIDENT;
        $rooms = $withRooms ? $this->roomBrowserPayload($roomFilters) : null;

        $avatar      = $this->avatarUrl($user);
        $firstName   = explode(' ', trim($user->name))[0] ?: 'there';
        $currentRoom = $state === self::STATE_RESIDENT ? $this->currentRoom($tenant, $now) : null;
        $active      = $pending ? $this->activeReservation($pending, $now) : null;
        $bills       = $this->bills($legacy, $now);
        $isApproved  = Tenant::isApproved();
        $servicesLocked = $isApproved && Tenant::hasUnsettledBalance($user->id);

        return [
            'tenantState'       => $state,
            'hasRoom'           => $state === self::STATE_RESIDENT,
            'hasUnpaidBills'    => $bills !== null,
            'activeReservation' => $active,

            'user' => [
                'name'      => $user->name,
                'firstName' => $firstName,
                'avatar'    => $avatar,
            ],
            'greeting'       => $this->greeting($now),
            'currentRoom'    => $currentRoom,
            'bills'          => $bills,
            'profile'        => $this->profileCompletion($user, $avatar),
            'announcements'  => $this->announcements($now),
            'activity'       => $this->recentActivity($user, $tenant),
            'unreadMessages' => $this->unreadMessages($tenant),
            'stats'          => $this->heroStats($state, $rooms, $currentRoom, $active),
            'access' => [
                'isTenant'       => $isApproved,
                'servicesLocked' => $servicesLocked,
                'canRoomChange'  => $this->canRoomChange($user, $tenant, $currentRoomId, $servicesLocked),
            ],
            'links' => [
                'reservations'  => '/tenants/reservations',
                'services'      => '/tenants/services/maintenance',
                'messages'      => '/tenants/messages',
                'roomChange'    => '/tenants/services/room-change',
                'history'       => '/tenants/history',
                'announcements' => '/tenants/announcements',
                'settings'      => '/tenants/settings',
                'houseRules'    => '/tenants/announcements',
            ],
            'rooms' => $rooms,
        ];
    }

    private function greeting($now): string
    {
        $h = (int) $now->format('G');
        return $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
    }

    private function avatarUrl($user): ?string
    {
        $pic = $user->personalInformation?->profile_picture ?? $user->profile_picture;
        if (! $pic) return null;
        return str_starts_with($pic, 'http') ? $pic : asset('storage/' . $pic);
    }

    /** Bill summary + the payload the existing Pay Now modal expects. Null when nothing is owed. */
    private function bills(array $legacy, $now): ?array
    {
        $total = (float) $legacy['outstandingBalance'];
        if ($total <= 0.005) {
            return null; // no bill UI anywhere when the balance is 0
        }

        $tenant = $legacy['tenant'];
        $payNow = $legacy['paymentPayload'] ?? [
            'reference'    => 'DASH-' . ($tenant?->id ?? Auth::id()),
            'title'        => 'Outstanding Balance',
            'amount'       => $total,
            'due_date'     => $now->format('M j, Y'),
            'gcash_number' => '0917-123-4567',
        ];

        $dues = collect($legacy['unpaidInvoices'])->pluck('due_date')
            ->push($legacy['remainingDue'])
            ->filter()
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d->toDateString(), self::TZ)->startOfDay());
        $nearest = $dues->sort()->first();
        $today   = $now->copy()->startOfDay();

        $flaggedOverdue = collect($legacy['unpaidInvoices'])->contains(fn ($i) => $i->status === 'Overdue');
        $pastDue     = $nearest && $nearest->lt($today);
        $isOverdue   = $flaggedOverdue || $pastDue;
        $daysOverdue = $pastDue ? (int) $nearest->diffInDays($today) : 0;
        $daysUntil   = $nearest && ! $isOverdue ? (int) $today->diffInDays($nearest) : null;

        return [
            'total'        => round($total, 2),
            'count'        => (int) $legacy['unpaidCount'],
            'nearestDue'   => $nearest?->toDateString(),
            'isOverdue'    => $isOverdue,
            'daysOverdue'  => $daysOverdue,
            'isDueSoon'    => ! $isOverdue && $daysUntil !== null && $daysUntil <= self::DUE_SOON_DAYS,
            'daysUntilDue' => $daysUntil,
            'payNow'       => $payNow,
        ];
    }

    private function currentRoom(?Tenant $tenant, $now): ?array
    {
        $room = $tenant?->room;
        if (! $room) return null;

        $stay  = $tenant->approvedReservation;
        $in    = $stay?->check_in ? \Illuminate\Support\Carbon::parse($stay->check_in->toDateString(), self::TZ) : null;
        $out   = $stay?->check_out ? \Illuminate\Support\Carbon::parse($stay->check_out->toDateString(), self::TZ) : null;
        $today = $now->copy()->startOfDay();

        $roommates = Tenant::with(['user.personalInformation'])
            ->where('room_id', $room->id)
            ->where('status', 'active')
            ->where('id', '!=', $tenant->id)
            ->get()
            ->map(fn (Tenant $t) => [
                'name'   => $t->user?->name ?? 'Roommate',
                'avatar' => $t->user ? $this->avatarUrl($t->user) : null,
            ])->values()->all();

        $totalDays = ($in && $out) ? max(1, (int) $in->diffInDays($out)) : null;
        $started   = $in && $in->lte($today);
        $daysIn    = $started ? (int) $in->diffInDays($today) + 1 : 0;

        $tour = $room->virtualTour?->status === 'Published' ? $room->virtualTour : null;

        return [
            'id'           => $room->id,
            'num'          => $room->room_number,
            'type'         => $room->room_type,
            'floor'        => $room->floor,
            'bed'          => $stay?->room_bed_id,
            'dailyRent'    => (float) $room->daily_rent,
            'img'          => RoomCatalogService::tourImage($tour),
            'tourId'       => $tour?->id,
            'moveIn'       => $in?->toDateString(),
            'contractEnd'  => $out?->toDateString(),
            'moveInInDays' => $in && ! $started ? (int) $today->diffInDays($in) : null,
            'daysStayed'   => $started ? ($totalDays ? min($daysIn, $totalDays) : $daysIn) : 0,
            'totalDays'    => $totalDays,
            'daysLeft'     => $out && $out->gte($today) ? (int) $today->diffInDays($out) : null,
            'roommates'    => $roommates,
        ];
    }

    private function activeReservation(Reservation $r, $now): array
    {
        $tour  = $r->room?->virtualTour?->status === 'Published' ? $r->room->virtualTour : null;
        $today = $now->copy()->startOfDay();
        $in    = $r->check_in ? \Illuminate\Support\Carbon::parse($r->check_in->toDateString(), self::TZ) : null;

        return [
            'id'        => $r->id,
            'reference' => $r->reference_no,
            'status'    => $r->status,
            // Requested (0) → Under Review (1) → Approved (2) → Move-in (3)
            'step'      => 1,
            'room'      => [
                'id'     => $r->room_id,
                'num'    => $r->room?->room_number ?? $r->desired_room,
                'type'   => $r->room?->room_type,
                'img'    => RoomCatalogService::tourImage($tour),
                'tourId' => $tour?->id,
            ],
            'bed'           => $r->room_bed_id,
            'checkIn'       => $r->check_in?->toDateString(),
            'checkOut'      => $r->check_out?->toDateString(),
            'moveInInDays'  => $in && $in->gte($today) ? (int) $today->diffInDays($in) : null,
            'submittedAt'   => $r->created_at?->toIso8601String(),
            'paymentOption' => $r->payment_option,
            'totalCharges'  => (float) $r->total_charges,
            'paidUpfront'   => (float) $r->reservation_fee,
            'remaining'     => (float) $r->remaining,
        ];
    }

    /** Real profile items only; each links to where it can actually be edited (null = nowhere). */
    private function profileCompletion($user, ?string $avatar): array
    {
        $pi = $user->personalInformation;
        $id = $user->identityVerification;

        $items = [
            [
                'key'   => 'photo',
                'label' => 'Add a profile photo',
                'done'  => (bool) $avatar,
                'href'  => '/tenants/settings',
            ],
            [
                'key'   => 'contact',
                'label' => 'Add your contact number',
                'done'  => (bool) ($pi?->contact_number ?: $user->phone),
                'href'  => $pi ? '/tenants/settings' : route('personal-information'),
            ],
            [
                'key'   => 'id',
                'label' => 'Upload a valid ID',
                'done'  => (bool) $id?->id_front,
                'href'  => $id?->id_front ? null : route('identity-verification'),
            ],
            [
                'key'   => 'emergency',
                'label' => 'Add an emergency contact',
                'done'  => (bool) ($pi?->emergency_name && $pi?->emergency_number),
                // Only editable during onboarding (personal information step)
                'href'  => $pi ? null : route('personal-information'),
            ],
        ];

        $done = collect($items)->where('done', true)->count();

        return [
            'percent' => (int) round($done / count($items) * 100),
            'items'   => $items,
        ];
    }

    /** No read tracking exists for announcements: "isNew" = published in the last 72 hours. */
    private function announcements($now): array
    {
        return Announcement::published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Announcement $a) use ($now) {
                $at = $a->published_at ?? $a->created_at;
                return [
                    'id'       => $a->id,
                    'title'    => $a->title,
                    'excerpt'  => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $a->content))), 180),
                    'pinned'   => (bool) $a->is_pinned,
                    'priority' => $a->priority,
                    'date'     => $at?->toIso8601String(),
                    'isNew'    => $at && $at->gte($now->copy()->subHours(self::NEW_ANNOUNCEMENT_HOURS)),
                ];
            })->values()->all();
    }

    /** Reservations, service requests and verified payments, newest first (max 5). */
    private function recentActivity($user, ?Tenant $tenant): array
    {
        $items = collect()->merge($this->historyReservations($user)->map(fn ($i) => $i + ['type' => 'reservation']));

        if ($tenant) {
            $payments = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['Paid', 'Partial'])
                ->whereNotNull('verified_at')
                ->latest('verified_at')
                ->limit(5)
                ->get()
                ->map(fn (Invoice $inv) => [
                    'key'       => 'pay-' . $inv->id,
                    'icon'      => 'fa-solid fa-peso-sign',
                    'title'     => 'Payment ₱' . number_format((float) $inv->amount_paid, 0) . ' — ' . \Illuminate\Support\Str::limit((string) $inv->description, 40),
                    'timestamp' => $inv->verified_at,
                    'status'    => $inv->status === 'Paid' ? 'paid' : 'partial',
                    'type'      => 'payment',
                ]);

            $items = $items
                ->merge($this->historyMaintenance($tenant)->map(fn ($i) => $i + ['type' => 'service']))
                ->merge($this->historyRoomChanges($tenant)->map(fn ($i) => $i + ['type' => 'room_change']))
                ->merge($this->historyStayExtensions($tenant)->map(fn ($i) => $i + ['type' => 'stay_extension']))
                ->merge($payments);
        }

        return $items->sortByDesc(fn ($i) => $i['timestamp'] ? \Illuminate\Support\Carbon::parse($i['timestamp'])->getTimestamp() : 0)
            ->take(5)
            ->map(fn ($i) => [
                'key'    => $i['key'],
                'type'   => $i['type'],
                'icon'   => $i['icon'],
                'title'  => $i['title'],
                'status' => $i['status'],
                'date'   => $i['timestamp'] ? \Illuminate\Support\Carbon::parse($i['timestamp'])->toIso8601String() : null,
            ])
            ->values()
            ->all();
    }

    private function unreadMessages(?Tenant $tenant): int
    {
        if (! $tenant) return 0;

        return \App\Models\Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.tenant_id', $tenant->id)
            ->where('conversations.deleted_by_tenant', false)
            ->whereNull('conversations.deleted_at')
            ->where('messages.sender_role', 'admin')
            ->whereNull('messages.read_at')
            ->whereNull('messages.deleted_at')
            ->count();
    }

    private function heroStats(string $state, ?array $rooms, ?array $currentRoom, ?array $active): array
    {
        if ($state === self::STATE_RESIDENT && $currentRoom) {
            $stats = [];
            if ($currentRoom['moveInInDays'] !== null) {
                $n = $currentRoom['moveInInDays'];
                $stats[] = ['icon' => 'fa-solid fa-key', 'value' => $n, 'label' => $n === 1 ? 'day to move-in' : 'days to move-in'];
            } elseif ($currentRoom['daysStayed'] > 0) {
                $stats[] = ['icon' => 'fa-solid fa-house-chimney', 'value' => $currentRoom['daysStayed'], 'prefix' => 'Day ',
                    'label' => $currentRoom['totalDays'] ? 'of ' . $currentRoom['totalDays'] . ' of your stay' : 'of your stay'];
                if ($currentRoom['daysLeft'] !== null) {
                    $n = $currentRoom['daysLeft'];
                    $stats[] = ['icon' => 'fa-regular fa-calendar', 'value' => $n, 'label' => $n === 1 ? 'day left' : 'days left'];
                }
            }
            $r = count($currentRoom['roommates']);
            $stats[] = ['icon' => 'fa-solid fa-user-group', 'value' => $r, 'label' => $r === 1 ? 'roommate' : 'roommates'];
            return $stats;
        }

        $stats = [];
        if ($rooms) {
            $openBeds = (int) ($rooms['openBeds'] ?? 0);
            $stats[] = ['icon' => 'fa-solid fa-bed', 'value' => $openBeds, 'label' => $openBeds === 1 ? 'bed open now' : 'beds open now'];
            $n = (int) $rooms['totalAvailable'];
            $stats[] = ['icon' => 'fa-solid fa-door-open', 'value' => $n, 'label' => $n === 1 ? 'room available' : 'rooms available'];
        }
        if ($state === self::STATE_PENDING && $active && $active['moveInInDays'] !== null) {
            $n = $active['moveInInDays'];
            $stats[] = ['icon' => 'fa-solid fa-key', 'value' => $n, 'label' => $n === 1 ? 'day to move-in' : 'days to move-in'];
        }

        return $stats;
    }

    private function canRoomChange($user, ?Tenant $tenant, ?int $currentRoomId, bool $servicesLocked): bool
    {
        return $currentRoomId !== null && ! $servicesLocked && Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('room_id', $tenant?->room_id)
            ->exists();
    }

    // ============================================================
    // ROOM BROWSER
    // ============================================================

    private const ROOMS_PER_PAGE = 12;
    public const ROOM_TYPES = ['Solo Room', 'Bedspace', 'Barkada Room'];
    private const ROOM_SORTS = ['recommended', 'price_asc', 'price_desc', 'beds', 'newest'];

    public function roomFilterRules(): array
    {
        return [
            'search'    => InputRules::search(),
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:' . InputRules::MONEY_MAX],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:' . InputRules::MONEY_MAX],
            'type'      => ['nullable', 'array', 'max:5'],
            'type.*'    => ['string', 'in:Solo Room,Bedspace,Barkada Room'],
            'floor'     => ['nullable', 'integer', 'min:1', 'max:' . InputRules::MAX_FLOORS],
            'sort'      => ['nullable', 'in:' . implode(',', self::ROOM_SORTS)],
            'page'      => ['nullable', 'integer', 'min:1', 'max:1000'],
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

        // "Available" = at least one free bed right now
        $available = $catalog->roomCards($mine, $currentRoomId)
            ->filter(fn ($c) => $c['is_available'])
            ->values();

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
        $types = array_values(array_filter((array) ($f['type'] ?? [])));
        $floor = isset($f['floor']) ? (int) $f['floor'] : null;
        $sort  = $f['sort'] ?? 'recommended';

        $filtered = $available->filter(function ($c) use ($search, $min, $max, $types, $floor) {
            if ($search !== '' && ! str_contains(mb_strtolower($c['num'] . ' ' . $c['name']), $search)) return false;
            if ($min !== null && $c['price'] < $min) return false;
            if ($max !== null && $c['price'] > $max) return false;
            if ($types && ! in_array($c['name'], $types, true)) return false;
            if ($floor !== null && (int) $c['floor'] !== $floor) return false;
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
                'search'    => $f['search'] ?? '',
                'min_price' => $min,
                'max_price' => $max,
                'type'      => $types,
                'floor'     => $floor,
                'sort'      => $sort,
            ],
            'facets' => [
                // All three room types are always filterable, with live counts
                'types'      => self::ROOM_TYPES,
                'typeCounts' => collect(self::ROOM_TYPES)
                    ->mapWithKeys(fn ($t) => [$t => $available->where('name', $t)->count()])
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
    // LEGACY BUILDER (balance, invoices, announcements, history)
    // ============================================================
    public function buildDashboardData($user): array
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
        $unpaidInvoices  = collect();
        $primaryInvoice  = null;
        $paymentPayload  = null;

        if ($tenant) {
            $rejectedRefs = \App\Models\Reservation::where('user_id', $user->id)
                ->whereIn('status', ['Rejected', 'Cancelled'])
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
            'unpaidInvoices'       => $unpaidInvoices,
            'reservationRemaining' => $reservationRemaining,
            'remainingDue'         => $reservationRemaining > 0 ? $reservation?->check_in : null,
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
                // Same rule as the expiry job: check-out date + time
                $isPast = $r->status === 'Approved' && $r->isStayOver();

                $status = match (true) {
                    $r->status === 'Approved' && $isPast => 'expired',
                    $r->status === 'Approved'            => 'approved',
                    $r->status === 'Rejected'            => 'rejected',
                    $r->status === 'Cancelled'           => 'cancelled',
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
