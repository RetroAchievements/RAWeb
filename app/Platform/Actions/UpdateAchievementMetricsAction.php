<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\RankType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Platform\Services\SearchIndexingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// TODO either convert to a service, or refactor so there's only one publicly-exposed function

class UpdateAchievementMetricsAction
{
    private const CHUNK_SIZE = 50;

    public function __construct(
        protected readonly CalculateAchievementWeightedPointsAction $calculateWeightedPoints,
    ) {
    }

    public function execute(Achievement $achievement): void
    {
        $this->update($achievement->game, collect([$achievement]));
    }

    /**
     * @param Collection<int, Achievement> $achievements
     */
    public function update(Game $game, Collection $achievements): void
    {
        // Bail early if there are no achievements to update.
        if ($achievements->isEmpty()) {
            return;
        }

        // Get both total and hardcore counts in a single query.
        $achievementIds = $achievements->pluck('id')->all();
        $unlockStats = PlayerAchievement::query()
            ->leftJoin('unranked_users', 'player_achievements.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.user_id')
            ->whereIn('player_achievements.achievement_id', $achievementIds)
            ->groupBy('player_achievements.achievement_id')
            ->selectRaw('
                player_achievements.achievement_id,
                COUNT(*) as total_unlocks,
                SUM(CASE WHEN unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) as hardcore_unlocks
            ')
            ->get();

        // Convert to lookup arrays for faster read access.
        $unlockCounts = [];
        $hardcoreUnlockCounts = [];
        foreach ($unlockStats as $stat) {
            $unlockCounts[$stat->achievement_id] = $stat->total_unlocks;
            $hardcoreUnlockCounts[$stat->achievement_id] = $stat->hardcore_unlocks;
        }

        $this->updateUsingUnlockCounts($game, $achievements, $unlockCounts, $hardcoreUnlockCounts);
    }

    /**
     * @param Collection<int, Achievement> $achievements
     */
    public function updateFromStoredUnlockCounts(Game $game, Collection $achievements): void
    {
        if ($achievements->isEmpty()) {
            return;
        }

        $unlockCounts = [];
        $hardcoreUnlockCounts = [];
        $achievementsRequiringRecount = collect();

        $storedUnlockCounts = Achievement::query()
            ->whereIn('id', $achievements->pluck('id')->all())
            ->get(['id', 'unlocks_total', 'unlocks_hardcore'])
            ->keyBy('id');

        foreach ($achievements as $achievement) {
            $storedUnlockCount = $storedUnlockCounts->get($achievement->id);

            if (!$storedUnlockCount || $storedUnlockCount->unlocks_total === null || $storedUnlockCount->unlocks_hardcore === null) {
                $achievementsRequiringRecount->push($achievement);

                continue;
            }

            $unlockCounts[$achievement->id] = (int) $storedUnlockCount->unlocks_total;
            $hardcoreUnlockCounts[$achievement->id] = (int) $storedUnlockCount->unlocks_hardcore;
        }

        if (!empty($unlockCounts)) {
            $skippedAchievementIds = $this->updateUsingUnlockCounts(
                $game,
                $achievements->whereIn('id', array_keys($unlockCounts)),
                $unlockCounts,
                $hardcoreUnlockCounts,
                useStoredUnlockCounts: true
            );

            if (!empty($skippedAchievementIds)) {
                $achievementsRequiringRecount = $achievementsRequiringRecount->merge(
                    Achievement::query()->whereIn('id', $skippedAchievementIds)->get()
                );
            }
        }

        if ($achievementsRequiringRecount->isNotEmpty()) {
            $this->update($game, $achievementsRequiringRecount->unique('id')->values());
        }
    }

    /**
     * @param Collection<int, Achievement> $achievements
     * @param array<int, int> $unlockCounts
     * @param array<int, int> $hardcoreUnlockCounts
     */
    private function updateUsingUnlockCounts(
        Game $game,
        Collection $achievements,
        array $unlockCounts,
        array $hardcoreUnlockCounts,
        bool $useStoredUnlockCounts = false,
    ): array {
        // NOTE if game has a parent game it contains the parent game's players metrics
        $playersTotal = $game->players_total;
        $playersHardcore = $game->players_hardcore ?? 0;
        $rankedPlayerCount = countRankedUsers(RankType::TruePoints);
        $searchIndexingService = app()->make(SearchIndexingService::class);

        $dirtyColumns = [
            'unlock_percentage',
            'unlock_hardcore_percentage',
            'points_weighted',
        ];

        if (!$useStoredUnlockCounts) {
            array_unshift($dirtyColumns, 'unlocks_total', 'unlocks_hardcore');
        }

        /**
         * In Horizon, each write requires an entire network round trip to the DB.
         * If there are hundreds of achievements to update, and each achievement
         * round trip takes 1-5ms, this could add up to additional second(s) of
         * processing time in the job just from pure network overhead. To mitigate
         * this, we'll do a single bulk update.
         */
        $bulkUpdates = [];

        foreach ($achievements as $achievement) {
            $unlocksCount = (int) ($unlockCounts[$achievement->id] ?? 0);
            $unlocksHardcoreCount = (int) ($hardcoreUnlockCounts[$achievement->id] ?? 0);

            $pointsWeighted = $this->calculateWeightedPoints->execute(
                $achievement->points,
                $unlocksHardcoreCount,
                $playersHardcore,
                $rankedPlayerCount
            );

            // Round percentages to 9 decimal places to match the exact database column precision (decimal(10,9)).
            // This prevents unnecessary updates due to precision differences in PHP.
            $unlockPercentage = round($playersTotal ? $unlocksCount / $playersTotal : 0, 9);
            $unlockHardcorePercentage = round($playersHardcore ? $unlocksHardcoreCount / $playersHardcore : 0, 9);

            // We'll optimistically set attributes on the model to leverage Laravel's dirty checking.
            // This doesn't necessarily mean we'll be doing a save for the model, though.
            if (!$useStoredUnlockCounts) {
                $achievement->unlocks_total = $unlocksCount;
                $achievement->unlocks_hardcore = $unlocksHardcoreCount;
            }

            $achievement->unlock_percentage = $unlockPercentage;
            $achievement->unlock_hardcore_percentage = $unlockHardcorePercentage;
            $achievement->points_weighted = $pointsWeighted;

            // Only actually add the achievement to the bulk updates list if the model has changed.
            if ($achievement->isDirty($dirtyColumns)) {
                $bulkUpdate = [
                    'id' => $achievement->id,
                    'unlock_percentage' => $unlockPercentage,
                    'unlock_hardcore_percentage' => $unlockHardcorePercentage,
                    'points_weighted' => $pointsWeighted,
                ];

                if (!$useStoredUnlockCounts) {
                    $bulkUpdate['unlocks_total'] = $unlocksCount;
                    $bulkUpdate['unlocks_hardcore'] = $unlocksHardcoreCount;
                }

                if ($useStoredUnlockCounts) {
                    $bulkUpdate['expected_unlocks_total'] = $unlocksCount;
                    $bulkUpdate['expected_unlocks_hardcore'] = $unlocksHardcoreCount;
                }

                $bulkUpdates[] = $bulkUpdate;

                $searchIndexingService->queueAchievementForIndexing($achievement->id);
            }
        }

        if (!empty($bulkUpdates)) {
            $skippedAchievementIds = $this->performBulkUpdate($bulkUpdates, $dirtyColumns, $useStoredUnlockCounts);
        } else {
            $skippedAchievementIds = [];
        }

        $game->points_weighted = $game->achievements()->promoted()->sum('points_weighted');
        if ($game->isDirty()) {
            $game->saveQuietly();

            // copy the new weighted points to the achievement set
            $coreGameAchievementSet = $game->gameAchievementSets()->core()->first();
            if ($coreGameAchievementSet) {
                $coreSet = $coreGameAchievementSet->achievementSet;
                $coreSet->points_weighted = $game->points_weighted;
                $coreSet->save();
            }

            $searchIndexingService->queueGameForIndexing($game->id);
        }

        return $skippedAchievementIds;
    }

    /**
     * Chunks the bulk update into smaller batches to reduce lock hold time.
     * During the weekly recalc, hundreds of jobs hit this table concurrently.
     * Smaller batches mean shorter lock windows and fewer deadlocks.
     */
    private function performBulkUpdate(array $bulkUpdates, array $columns, bool $useStoredUnlockCounts = false): array
    {
        usort($bulkUpdates, fn ($a, $b) => $a['id'] <=> $b['id']);

        $skippedAchievementIds = [];
        foreach (array_chunk($bulkUpdates, self::CHUNK_SIZE) as $chunk) {
            array_push(
                $skippedAchievementIds,
                ...$this->updateChunk($chunk, $columns, $useStoredUnlockCounts)
            );
        }

        return $skippedAchievementIds;
    }

    /**
     * Executes the CASE-based bulk update within a transaction that
     * automatically retries on deadlocks (via DB::transaction's second argument).
     */
    private function updateChunk(array $chunk, array $columns, bool $useStoredUnlockCounts): array
    {
        $cases = [];
        foreach ($columns as $column) {
            $whens = implode(' ', array_map(
                fn ($row) => "WHEN {$row['id']} THEN {$row[$column]}",
                $chunk,
            ));
            $cases[$column] = DB::raw("CASE id {$whens} END");
        }

        $cases['updated_at'] = now();

        $ids = array_column($chunk, 'id');
        $skippedAchievementIds = [];

        DB::transaction(function () use ($ids, $cases, $chunk, $useStoredUnlockCounts, &$skippedAchievementIds) {
            $applyStoredUnlockCountGuard = function ($query) use ($chunk) {
                return $query->where(function ($query) use ($chunk) {
                    foreach ($chunk as $row) {
                        $query->orWhere(function ($query) use ($row) {
                            $query
                                ->where('id', $row['id'])
                                ->where('unlocks_total', $row['expected_unlocks_total'])
                                ->where('unlocks_hardcore', $row['expected_unlocks_hardcore']);
                        });
                    }
                });
            };

            $query = DB::table('achievements')->whereIn('id', $ids);

            if ($useStoredUnlockCounts) {
                $query = $applyStoredUnlockCountGuard($query);
            }

            $query->update($cases);

            if ($useStoredUnlockCounts) {
                $matchedAchievementIds = $applyStoredUnlockCountGuard(DB::table('achievements')->whereIn('id', $ids))
                    ->pluck('id')
                    ->all();

                $skippedAchievementIds = array_values(array_diff($ids, $matchedAchievementIds));
            }
        }, attempts: 5);

        return $skippedAchievementIds;
    }
}
