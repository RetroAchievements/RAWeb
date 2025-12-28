<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Services\SearchIndexingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateAchievementMetricsAction
{
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
        $playersHardcore = $game->players_hardcore;
        $playersHardcoreCalc = $playersHardcore ?: 1;

        // Get both total and hardcore counts in a single query.
        $achievementIds = $achievements->pluck('id')->all();
        $unlockStats = PlayerAchievement::query()
            ->whereIn('player_achievements.achievement_id', $achievementIds)
            ->whereHas('user', function ($query) {
                /** @var Builder<User> $query */
                $query->tracked();
            })
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
            $unlocksHardcoreCount = $hardcoreUnlockCounts[$achievement->id] ?? 0;

            // force all unachieved to be 1
            $unlocksHardcoreCalc = $unlocksHardcoreCount ?: 1;
            $weight = 0.4;
            $pointsWeighted = (int) (
                $achievement->points * (1 - $weight)
                + $achievement->points * (($playersHardcoreCalc / $unlocksHardcoreCalc) * $weight)
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

        $game->TotalTruePoints = $game->achievements()->published()->sum('points_weighted');
        if ($game->isDirty()) {
            $game->saveQuietly();

            // copy the new weighted points to the achievement set
            $coreGameAchievementSet = $game->gameAchievementSets()->core()->first();
            if ($coreGameAchievementSet) {
                $coreSet = $coreGameAchievementSet->achievementSet;
                $coreSet->points_weighted = $game->TotalTruePoints;
                $coreSet->save();
            }

            $searchIndexingService->queueGameForIndexing($game->id);
        }
    }

    /**
     * In Horizon, each write requires an entire network round trip to the DB.
     * If there are hundreds of achievements to update, and each achievement
     * round trip takes 1-5ms, this could add up to additional second(s) of
     * processing time in the job just from pure network overhead. To mitigate
     * this, we'll do a single bulk update.
     */
    private function performBulkUpdate(array $bulkUpdates): void
    {
        // Build a bulk UPDATE query using CASE statements to update all achievements in a single DB statement.

        /*
         * The final query will look like this:
         *
         * UPDATE achievements
         * SET
         *   unlocks_total = CASE id
         *     WHEN X THEN Y
         *   END,
         *   unlocks_hardcore = CASE id
         *     WHEN X THEN Y
         *   END,
         *   unlock_percentage = CASE id
         *     WHEN X THEN Y
         *   END,
         *   unlock_hardcore_percentage = CASE id
         *     WHEN X THEN Y
         *   END,
         *   points_weighted = CASE id
         *     WHEN X THEN Y
         *   END,
         *   Updated = '...'
         * WHERE id IN ( ... )
         */
        $ids = array_column($bulkUpdates, 'id');
        $cases = [
            'unlocks_total' => 'CASE id',
            'unlocks_hardcore' => 'CASE id',
            'unlock_percentage' => 'CASE id',
            'unlock_hardcore_percentage' => 'CASE id',
            'points_weighted' => 'CASE id',
        ];

        foreach ($bulkUpdates as $update) {
            $cases['unlocks_total'] .= " WHEN {$update['id']} THEN {$update['unlocks_total']}";
            $cases['unlocks_hardcore'] .= " WHEN {$update['id']} THEN {$update['unlocks_hardcore']}";
            $cases['unlock_percentage'] .= " WHEN {$update['id']} THEN {$update['unlock_percentage']}";
            $cases['unlock_hardcore_percentage'] .= " WHEN {$update['id']} THEN {$update['unlock_hardcore_percentage']}";
            $cases['points_weighted'] .= " WHEN {$update['id']} THEN {$update['points_weighted']}";
        }

        foreach ($cases as &$case) {
            $case .= ' END';
        }

        // Use DB to bypass model events.
        DB::table('achievements')
            ->whereIn('id', $ids)
            ->update([
                'unlocks_total' => DB::raw($cases['unlocks_total']),
                'unlocks_hardcore' => DB::raw($cases['unlocks_hardcore']),
                'unlock_percentage' => DB::raw($cases['unlock_percentage']),
                'unlock_hardcore_percentage' => DB::raw($cases['unlock_hardcore_percentage']),
                'points_weighted' => DB::raw($cases['points_weighted']),
                'updated_at' => now(),
            ]);
    }
}
