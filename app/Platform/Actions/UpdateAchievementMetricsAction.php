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

        // NOTE if game has a parent game it contains the parent game's players metrics
        $playersTotal = $game->players_total;
        $playersHardcore = $game->players_hardcore ?? 0;
        $rankedPlayerCount = countRankedUsers(RankType::TruePoints);

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

        $searchIndexingService = app()->make(SearchIndexingService::class);

        /**
         * In Horizon, each write requires an entire network round trip to the DB.
         * If there are hundreds of achievements to update, and each achievement
         * round trip takes 1-5ms, this could add up to additional second(s) of
         * processing time in the job just from pure network overhead. To mitigate
         * this, we'll do a single bulk update.
         */
        $bulkUpdates = [];

        foreach ($achievements as $achievement) {
            $unlocksCount = $unlockCounts[$achievement->id] ?? 0;
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
            $achievement->unlocks_total = $unlocksCount;
            $achievement->unlocks_hardcore = $unlocksHardcoreCount;
            $achievement->unlock_percentage = $unlockPercentage;
            $achievement->unlock_hardcore_percentage = $unlockHardcorePercentage;
            $achievement->points_weighted = $pointsWeighted;

            // Only actually add the achievement to the bulk updates list if the model has changed.
            if ($achievement->isDirty()) {
                $bulkUpdates[] = [
                    'id' => $achievement->id,
                    'unlocks_total' => $unlocksCount,
                    'unlocks_hardcore' => $unlocksHardcoreCount,
                    'unlock_percentage' => $unlockPercentage,
                    'unlock_hardcore_percentage' => $unlockHardcorePercentage,
                    'points_weighted' => $pointsWeighted,
                ];

                $searchIndexingService->queueAchievementForIndexing($achievement->id);
            }
        }

        if (!empty($bulkUpdates)) {
            $this->performBulkUpdate($bulkUpdates);
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
    }

    /**
     * Chunks the bulk update into smaller batches to reduce lock hold time.
     * During the weekly recalc, hundreds of jobs hit this table concurrently.
     * Smaller batches mean shorter lock windows and fewer deadlocks.
     */
    private function performBulkUpdate(array $bulkUpdates): void
    {
        usort($bulkUpdates, fn ($a, $b) => $a['id'] <=> $b['id']);

        foreach (array_chunk($bulkUpdates, self::CHUNK_SIZE) as $chunk) {
            $this->updateChunk($chunk);
        }
    }

    /**
     * Executes the CASE-based bulk update within a transaction that
     * automatically retries on deadlocks (via DB::transaction's second argument).
     */
    private function updateChunk(array $chunk): void
    {
        $columns = [
            'unlocks_total',
            'unlocks_hardcore',
            'unlock_percentage',
            'unlock_hardcore_percentage',
            'points_weighted',
        ];

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

        DB::transaction(function () use ($ids, $cases) {
            DB::table('achievements')
                ->whereIn('id', $ids)
                ->update($cases);
        }, attempts: 5);
    }
}
