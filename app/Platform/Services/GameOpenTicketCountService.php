<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\TicketableType;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

class GameOpenTicketCountService
{
    private const FRESH_SECONDS = 5 * 60;
    private const STALE_SECONDS = 15 * 60;

    public function count(Game $game, bool $isPromoted): int
    {
        return (int) Cache::flexible(
            CacheKey::buildGameOpenTicketsCacheKey($game->id, $isPromoted),
            [self::FRESH_SECONDS, self::STALE_SECONDS],
            fn () => $this->countUncached($game, $isPromoted),
        );
    }

    public function clearForTicket(Ticket $ticket): void
    {
        $this->clearForTicketable(
            $ticket->ticketable_type,
            $ticket->ticketable_id !== null ? (int) $ticket->ticketable_id : null,
        );
    }

    public function clearForTicketable(?string $ticketableType, ?int $ticketableId): void
    {
        if (!$ticketableType || !$ticketableId) {
            return;
        }

        $gameId = match ($ticketableType) {
            TicketableType::Achievement->value => Achievement::withTrashed()
                ->whereKey($ticketableId)
                ->value('game_id'),
            TicketableType::Leaderboard->value => Leaderboard::withTrashed()
                ->whereKey($ticketableId)
                ->value('game_id'),
            default => null,
        };

        if ($gameId !== null) {
            $this->clearForGameId($gameId);
        }
    }

    public function clearForGameId(int $gameId): void
    {
        Cache::forget(CacheKey::buildGameOpenTicketsCacheKey($gameId, true));
        Cache::forget(CacheKey::buildGameOpenTicketsCacheKey($gameId, false));
    }

    private function countUncached(Game $game, bool $isPromoted): int
    {
        return $this->countAchievementTickets($game, $isPromoted) + $this->countLeaderboardTickets($game, $isPromoted);
    }

    private function countAchievementTickets(Game $game, bool $isPromoted): int
    {
        $achievementIds = Achievement::query()
            ->where('achievements.game_id', $game->id)
            ->where('achievements.is_promoted', $isPromoted)
            ->pluck('achievements.id');

        if ($achievementIds->isEmpty()) {
            return 0;
        }

        return Ticket::query()
            ->where('ticketable_type', TicketableType::Achievement->value)
            ->whereIn('ticketable_id', $achievementIds)
            ->whereIn('state', [TicketState::Open->value, TicketState::Request->value])
            ->count();
    }

    private function countLeaderboardTickets(Game $game, bool $isPromoted): int
    {
        $query = Leaderboard::query()->where('leaderboards.game_id', $game->id);

        if ($isPromoted) {
            $query->where('leaderboards.state', '!=', LeaderboardState::Unpromoted->value);
        } else {
            $query->where('leaderboards.state', LeaderboardState::Unpromoted->value);
        }

        $leaderboardIds = $query->pluck('leaderboards.id');

        if ($leaderboardIds->isEmpty()) {
            return 0;
        }

        return Ticket::query()
            ->where('ticketable_type', TicketableType::Leaderboard->value)
            ->whereIn('ticketable_id', $leaderboardIds)
            ->whereIn('state', [TicketState::Open->value, TicketState::Request->value])
            ->count();
    }
}
