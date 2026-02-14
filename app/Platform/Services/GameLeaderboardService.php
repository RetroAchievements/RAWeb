<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Data\LeaderboardData;
use App\Platform\Data\LeaderboardEntryData;
use App\Platform\Enums\LeaderboardState;
use Illuminate\Support\Collection;

class GameLeaderboardService
{
    /**
     * @param Collection<int, LeaderboardEntry> $userEntries keyed by leaderboard_id
     * @param array<int, int> $userRanks maps leaderboard_id to rank
     * @return Collection<int, LeaderboardData>
     */
    public function buildLeaderboards(
        Game $game,
        ?User $user,
        Collection $userEntries,
        array $userRanks,
        ?int $limit = null,
        bool $shouldIncludeActiveOnly = false,
        bool $showUnpublished = false,
    ): Collection {
        if (!$game->system->active || $game->system->id === System::Events) {
            return collect();
        }

        $allowedLeaderboardStates = match (true) {
            $shouldIncludeActiveOnly => [LeaderboardState::Active],
            $showUnpublished => [LeaderboardState::Unpromoted],
            default => [LeaderboardState::Active, LeaderboardState::Disabled],
        };

        $leaderboards = $game->leaderboards
            ->whereIn('state', $allowedLeaderboardStates)
            ->values();

        if (!$shouldIncludeActiveOnly) {
            // Active/Unpromoted first, Disabled last, then by order_column.
            $leaderboards = $leaderboards->sortBy([
                fn ($leaderboard) => $leaderboard->state === LeaderboardState::Disabled ? 1 : 0,
                fn ($a, $b) => $a->order_column <=> $b->order_column,
            ])->values();
        }

        if ($limit !== null) {
            $leaderboards = $leaderboards->take($limit);
        }

        return $leaderboards->map(function ($leaderboard) use ($userEntries, $userRanks, $user) {
            $userEntryData = null;
            if ($user && $userEntries->has($leaderboard->id)) {
                $userEntry = $userEntries->get($leaderboard->id);
                $rank = $userRanks[$leaderboard->id] ?? 1;

                $userEntryData = LeaderboardEntryData::fromLeaderboardEntry(
                    $userEntry,
                    $leaderboard->format,
                    $rank,
                )->include('formattedScore', 'rank');
            }

            return LeaderboardData::fromLeaderboard($leaderboard, $userEntryData)->include(
                'description',
                'format',
                'rankAsc',
                'title',
                'topEntry.formattedScore',
                'topEntry.user.avatarUrl',
                'topEntry.user.displayName',
                'userEntry',
                'state',
            );
        });
    }

    /**
     * Fetch user leaderboard entries and pre-calculate all ranks in a single
     * batch query to avoid N+1 queries when building individual leaderboards.
     *
     * @return array{Collection<int, LeaderboardEntry>, array<int, int>}
     */
    public function getUserLeaderboardData(Game $game, ?User $user): array
    {
        if (!$user) {
            return [collect(), []];
        }

        $leaderboardIds = $game->leaderboards->pluck('id');

        $userEntries = LeaderboardEntry::whereIn('leaderboard_id', $leaderboardIds)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('leaderboard_id');

        $ranks = $this->calculateBatchRanks($game->leaderboards, $userEntries);

        return [$userEntries, $ranks];
    }

    public function getCount(Game $game, bool $isViewingPublishedAchievements): int
    {
        if (!$game->system->active || $game->system->id === System::Events) {
            return 0;
        }

        return $game->leaderboards
            ->where('state', $isViewingPublishedAchievements ? LeaderboardState::Active : LeaderboardState::Unpromoted)
            ->count();
    }

    /**
     * Calculate the authenticated user's ranks for multiple leaderboards in a
     * single query by building per-leaderboard subqueries combined with UNION ALL.
     *
     * @param Collection<int, Leaderboard> $leaderboards
     * @param Collection<int, LeaderboardEntry> $userEntries keyed by leaderboard_id
     * @return array<int, int> maps leaderboard_id to rank
     */
    private function calculateBatchRanks(Collection $leaderboards, Collection $userEntries): array
    {
        if ($userEntries->isEmpty()) {
            return [];
        }

        $leaderboardsById = $leaderboards->keyBy('id');

        $subqueries = [];
        foreach ($userEntries as $leaderboardId => $entry) {
            $leaderboard = $leaderboardsById->get($leaderboardId);
            if (!$leaderboard) {
                continue;
            }

            $scoreComparison = $leaderboard->rank_asc ? '<' : '>';

            $subqueries[] = LeaderboardEntry::query()
                ->selectRaw('? as leaderboard_id, COUNT(*) as better_count', [$leaderboardId])
                ->leftJoin('unranked_users', 'leaderboard_entries.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->where('leaderboard_entries.leaderboard_id', $leaderboardId)
                ->where('leaderboard_entries.score', $scoreComparison, $entry->score);
        }

        if (empty($subqueries)) {
            return [];
        }

        $combinedQuery = array_shift($subqueries);
        foreach ($subqueries as $subquery) {
            $combinedQuery = $combinedQuery->unionAll($subquery);
        }

        $ranks = [];
        foreach ($combinedQuery->get() as $row) {
            $ranks[$row->leaderboard_id] = (int) $row->better_count + 1;
        }

        return $ranks;
    }
}
