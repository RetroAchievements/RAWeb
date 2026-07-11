<?php

declare(strict_types=1);

namespace App\Api\V2\Actions;

use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\UnrankedUser;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildGameAchievementDistributionHistogramAction
{
    /**
     * @return array{
     *   promoted: array{totalAchievements: int, distribution: array<int, array{unlockCount: int, playersHardcore: int, playersCasual: int}>},
     *   unpromoted: array{totalAchievements: int, distribution: array<int, array{unlockCount: int, playersHardcore: int, playersCasual: int}>},
     * }
     */
    public function execute(Game $game, ?User $requestedBy): array
    {
        // Unranked users never count toward the distribution, except the
        // requester, who always sees their own progress reflected.
        $excludedUserIds = UnrankedUser::pluck('user_id')
            ->reject(fn (int $userId) => $userId === $requestedBy?->id)
            ->all();

        return [
            'promoted' => $this->buildGroup($game, $excludedUserIds, isPromoted: true),
            'unpromoted' => $this->buildGroup($game, $excludedUserIds, isPromoted: false),
        ];
    }

    /**
     * @param array<int> $excludedUserIds
     * @return array{totalAchievements: int, distribution: array<int, array{unlockCount: int, playersHardcore: int, playersCasual: int}>}
     */
    private function buildGroup(Game $game, array $excludedUserIds, bool $isPromoted): array
    {
        $totalAchievements = $game->achievements()->where('is_promoted', $isPromoted)->count();
        if ($totalAchievements === 0) {
            return ['totalAchievements' => 0, 'distribution' => []];
        }

        [$hardcore, $casual] = $isPromoted
            ? $this->promotedCounts($game, $excludedUserIds)
            : $this->unpromotedCounts($game, $excludedUserIds);

        $distribution = [];
        for ($unlockCount = 1; $unlockCount <= $totalAchievements; $unlockCount++) {
            $distribution[] = [
                'unlockCount' => $unlockCount,
                'playersHardcore' => (int) $hardcore->get($unlockCount, 0),
                'playersCasual' => (int) $casual->get($unlockCount, 0),
            ];
        }

        return [
            'totalAchievements' => $totalAchievements,
            'distribution' => $distribution,
        ];
    }

    /**
     * Counts players per exact unlock total using the denormalized per-game
     * counters. Filtering excluded users inline would force a row lookup for
     * every player of the game, so instead the full histograms come from
     * covering index scans and the excluded players' contribution gets subtracted.
     *
     * @param array<int> $excludedUserIds
     * @return array{0: Collection<int|string, int<1, max>>, 1: Collection<int|string, int<1, max>>} hardcore and casual maps of unlock count to player count
     */
    private function promotedCounts(Game $game, array $excludedUserIds): array
    {
        $allPlayersFor = fn (string $countColumn): Collection => PlayerGame::query()
            ->where('game_id', $game->id)
            ->where($countColumn, '>', 0)
            ->selectRaw("{$countColumn} as unlock_count, count(*) as players")
            ->groupBy('unlock_count')
            ->pluck('players', 'unlock_count');

        $excludedPairs = PlayerGame::query()
            ->where('game_id', $game->id)
            ->whereIntegerInRaw('user_id', $excludedUserIds)
            ->where(fn ($query) => $query
                ->where('achievements_unlocked_hardcore', '>', 0)
                ->orWhere('achievements_unlocked_softcore', '>', 0))
            ->selectRaw('achievements_unlocked_hardcore as hardcore_unlocks, achievements_unlocked_softcore as casual_unlocks, count(*) as players')
            ->groupBy('hardcore_unlocks', 'casual_unlocks')
            ->get();

        [$excludedHardcore, $excludedCasual] = $this->foldPairCounts($excludedPairs);

        $subtract = fn (Collection $allPlayers, Collection $excluded): Collection => $allPlayers
            ->map(fn ($players, $unlockCount) => (int) $players - (int) $excluded->get($unlockCount, 0))
            ->filter(fn (int $players) => $players > 0);

        return [
            $subtract($allPlayersFor('achievements_unlocked_hardcore'), $excludedHardcore),
            $subtract($allPlayersFor('achievements_unlocked_softcore'), $excludedCasual),
        ];
    }

    /**
     * Unpromoted achievements have no denormalized counters, so per-user unlock
     * totals come from player_achievements.
     *
     * @param array<int> $excludedUserIds
     * @return array{0: Collection<int|string, int>, 1: Collection<int|string, int>} hardcore and casual maps of unlock count to player count
     */
    private function unpromotedCounts(Game $game, array $excludedUserIds): array
    {
        $perUserTotals = PlayerAchievement::query()
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
            ->where('achievements.game_id', $game->id)
            ->where('achievements.is_promoted', false)
            ->whereIntegerNotInRaw('player_achievements.user_id', $excludedUserIds)
            ->selectRaw(
                'player_achievements.user_id,
                sum(case when player_achievements.unlocked_hardcore_at is not null then 1 else 0 end) as hardcore_unlocks,
                sum(case when player_achievements.unlocked_hardcore_at is null then 1 else 0 end) as casual_unlocks'
            )
            ->groupBy('player_achievements.user_id');

        $pairCounts = PlayerAchievement::query()
            ->fromSub($perUserTotals, 'sub')
            ->selectRaw('sub.hardcore_unlocks, sub.casual_unlocks, count(*) as players')
            ->groupBy('sub.hardcore_unlocks', 'sub.casual_unlocks')
            ->get();

        return $this->foldPairCounts($pairCounts);
    }

    /**
     * Folds rows of (hardcore_unlocks, casual_unlocks, players) into separate
     * hardcore and casual histograms, dropping zero-unlock buckets.
     *
     * @param Collection<int, mixed> $pairCounts
     * @return array{0: Collection<int|string, int>, 1: Collection<int|string, int>} hardcore and casual maps of unlock count to player count
     */
    private function foldPairCounts(Collection $pairCounts): array
    {
        $histogramFor = fn (string $countColumn): Collection => $pairCounts
            ->where($countColumn, '>', 0)
            ->groupBy($countColumn)
            ->map(fn (Collection $pairs) => (int) $pairs->sum('players'));

        return [$histogramFor('hardcore_unlocks'), $histogramFor('casual_unlocks')];
    }
}
