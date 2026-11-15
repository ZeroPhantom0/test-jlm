<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ranks bookable rooms for a tenant with a simple, explainable match score.
 *
 * Factor weights (points):
 *   price fit ........ 40  vs. rent of rooms the tenant booked/lived in before
 *                           (median available price when there's no history)
 *   same room type ... 20  as the tenant's most recent reservation
 *   free beds ........ 15  share of the room's beds still free (less crowded)
 *   popularity ....... 10  reservations made in the last 90 days
 *   amenities ........ 10  SKIPPED — rooms have no amenities data
 *   recently added ...  5  room created in the last 60 days
 *                           (there's no "renovated" date, so created_at is used)
 *
 * A factor with no data for this tenant is skipped and the score is
 * normalised over the factors that did apply, so it still reads 0–100.
 * Rooms the tenant was rejected for in the last 30 days are excluded.
 */
class RoomRecommendationService
{
    public const W_PRICE      = 40;
    public const W_TYPE       = 20;
    public const W_BEDS       = 15;
    public const W_POPULARITY = 10;
    public const W_AMENITIES  = 10;
    public const W_RECENT     = 5;

    private const POPULARITY_DAYS = 90;
    private const RECENT_DAYS     = 60;
    private const REJECTED_DAYS   = 30;

    /**
     * @param  Collection  $cards           room cards from RoomCatalogService
     * @param  Collection  $myReservations  the tenant's reservations
     * @return array{rooms: array, factors: array}
     */
    public function recommend(Collection $cards, Collection $myReservations, ?Tenant $tenant, ?int $limit = 4): array
    {
        $candidates = $cards->filter(fn ($c) => $c['is_bookable'])->values();

        // ── Exclude rooms the tenant was rejected for recently ──
        $rejectedRoomIds = $myReservations
            ->filter(fn ($r) => $r->status === 'Rejected' && $r->room_id
                && ($r->processed_at ?? $r->updated_at)?->gte(now()->subDays(self::REJECTED_DAYS)))
            ->pluck('room_id')
            ->unique()
            ->all();
        $candidates = $candidates->reject(fn ($c) => in_array($c['id'], $rejectedRoomIds, true))->values();

        if ($candidates->isEmpty()) {
            return ['rooms' => [], 'factors' => $this->factorReport([], false)];
        }

        // ── Tenant history: rent of rooms they booked or live in ──
        $historyRoomIds = $myReservations->pluck('room_id')
            ->push($tenant?->room_id)
            ->filter()
            ->unique()
            ->values();
        $historyPrices = $historyRoomIds->isEmpty()
            ? collect()
            : Room::whereIn('id', $historyRoomIds)->pluck('daily_rent')->map(fn ($p) => (float) $p)->filter(fn ($p) => $p > 0);

        $hasPriceHistory = $historyPrices->isNotEmpty();
        $priceLow  = $hasPriceHistory ? $historyPrices->min() : $this->median($candidates->pluck('price'));
        $priceHigh = $hasPriceHistory ? $historyPrices->max() : $priceLow;

        $lastType = $myReservations->first(fn ($r) => $r->room_id)?->room?->room_type
            ?? ($tenant?->room?->room_type);

        // ── Popularity: reservations per room in the last 90 days ──
        $popularity = Reservation::whereIn('room_id', $candidates->pluck('id'))
            ->where('created_at', '>=', now()->subDays(self::POPULARITY_DAYS))
            ->selectRaw('room_id, COUNT(*) as c')
            ->groupBy('room_id')
            ->pluck('c', 'room_id');
        $maxPopularity = (int) $popularity->max();

        $usePopularity = $maxPopularity > 0;
        $useType       = (bool) $lastType;

        $maxPoints = self::W_PRICE + self::W_BEDS + self::W_RECENT
            + ($useType ? self::W_TYPE : 0)
            + ($usePopularity ? self::W_POPULARITY : 0);

        $scored = $candidates->map(function (array $card) use (
            $priceLow, $priceHigh, $hasPriceHistory, $lastType, $useType,
            $popularity, $maxPopularity, $usePopularity, $maxPoints
        ) {
            $parts = [];

            // Price fit: full points inside the band (±10%), fading to 0 at 50% away
            $price = (float) $card['price'];
            $lo = $priceLow * 0.9;
            $hi = $priceHigh * 1.1;
            if ($price >= $lo && $price <= $hi) {
                $pricePts = self::W_PRICE;
            } else {
                $edge     = $price < $lo ? $lo : $hi;
                $distance = $edge > 0 ? abs($price - $edge) / $edge : 1;
                $pricePts = self::W_PRICE * max(0, 1 - $distance * 2);
            }
            $parts['price'] = [
                $pricePts,
                $pricePts >= self::W_PRICE * 0.75
                    ? ($hasPriceHistory ? 'Within your budget' : 'Typical price here')
                    : null,
            ];

            if ($useType) {
                $match = $card['name'] === $lastType;
                $parts['type'] = [$match ? self::W_TYPE : 0, $match ? 'Same type you booked before' : null];
            }

            // Free beds: share of beds still free (whole-room bookings count as fully free)
            $freeShare = $card['total_beds'] > 0 ? $card['empty_beds'] / $card['total_beds'] : 0;
            $bedsPts   = self::W_BEDS * min(1, $freeShare);
            $parts['beds'] = [
                $bedsPts,
                $card['per_bed']
                    ? ($freeShare >= 0.5 ? 'Plenty of free beds' : null)
                    : 'Whole room to yourself',
            ];

            if ($usePopularity) {
                $count  = (int) ($popularity[$card['id']] ?? 0);
                $popPts = self::W_POPULARITY * ($count / $maxPopularity);
                $parts['popularity'] = [$popPts, $count > 0 && $count === $maxPopularity ? 'Popular lately' : null];
            }

            $created = $card['created_at'] ? Carbon::parse($card['created_at']) : null;
            $isNew   = $created && $created->gte(now()->subDays(self::RECENT_DAYS));
            $parts['recent'] = [$isNew ? self::W_RECENT : 0, $isNew ? 'Newly added' : null];

            $points = array_sum(array_column($parts, 0));

            $reasons = collect($parts)
                ->filter(fn ($p) => $p[1] !== null && $p[0] > 0)
                ->sortByDesc(fn ($p) => $p[0])
                ->pluck(1)
                ->take(3)
                ->values()
                ->all();

            return $card + [
                'matchScore' => (int) round($maxPoints > 0 ? $points / $maxPoints * 100 : 0),
                'reasons'    => $reasons,
            ];
        });

        $rooms = $scored
            ->sortBy([['matchScore', 'desc'], ['empty_beds', 'desc'], ['price', 'asc']])
            ->when($limit !== null, fn ($c) => $c->take($limit))
            ->values()
            ->all();

        return [
            'rooms'   => $rooms,
            'factors' => $this->factorReport(['type' => $useType, 'popularity' => $usePopularity], $hasPriceHistory),
        ];
    }

    /** Which factors applied — surfaced so the UI/devs can see what's skipped. */
    private function factorReport(array $used, bool $hasPriceHistory): array
    {
        return [
            'price'      => $hasPriceHistory ? 'history' : 'median',
            'type'       => ($used['type'] ?? false) ? 'used' : 'skipped: no previous reservation',
            'beds'       => 'used',
            'popularity' => ($used['popularity'] ?? false) ? 'used' : 'skipped: no reservations in the last 90 days',
            'amenities'  => 'skipped: rooms have no amenities data',
            'recent'     => 'used (created_at; no renovation date)',
        ];
    }

    private function median(Collection $values): float
    {
        $sorted = $values->map(fn ($v) => (float) $v)->sort()->values();
        $n = $sorted->count();
        if ($n === 0) return 0.0;

        return $n % 2 ? $sorted[intdiv($n, 2)] : ($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2;
    }
}
