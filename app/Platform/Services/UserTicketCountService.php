<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserTicketCountService
{
    private const TTL_HOURS = 20;

    /**
     * Count a dev's open and pending response tickets (where they are the assignee).
     *
     * @return array{string: int}
     */
    public function countOpenForDev(User $dev): array
    {
        return Cache::remember(
            CacheKey::buildUserOpenTicketsCacheKey($dev->id),
            Carbon::now()->addHours(self::TTL_HOURS),
            function () use ($dev): array {
                $retVal = [
                    TicketState::Open->value => 0,
                    TicketState::Request->value => 0,
                ];

                $counts = Ticket::query()
                    ->where('ticketable_author_id', $dev->id)
                    ->whereHas('achievement')
                    ->whereIn('state', [TicketState::Open, TicketState::Request])
                    ->select('state', DB::raw('count(*) as Count'))
                    ->groupBy('state')
                    ->pluck('Count', 'state');

                foreach ($counts as $state => $count) {
                    $retVal[$state] = (int) $count;
                }

                return $retVal;
            },
        );
    }

    /**
     * Count tickets in the Request state where the given user is the reporter.
     */
    public function countRequestsForReporter(User $reporter): int
    {
        return (int) Cache::remember(
            CacheKey::buildUserRequestTicketsCacheKey($reporter->id),
            Carbon::now()->addHours(self::TTL_HOURS),
            fn (): int => Ticket::where('state', TicketState::Request)
                ->where('reporter_id', $reporter->id)
                ->count(),
        );
    }

    public function clearForUserId(int $userId): void
    {
        Cache::forget(CacheKey::buildUserRequestTicketsCacheKey($userId));
        Cache::forget(CacheKey::buildUserOpenTicketsCacheKey($userId));
    }

    /**
     * Bulk cachebust over a set of user ids.
     * Used by code paths that mutate ticket rows in bulk and therefore bypass Eloquent events / observers.
     */
    public function clearForUserIds(iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            try {
                $this->clearForUserId((int) $userId);
            } catch (Throwable) {
                // swallow the error if this fails, it's no big deal
            }
        }
    }
}
