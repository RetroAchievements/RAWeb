<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Platform\Services\SearchIndexingService;
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
        $achievementIds = $achievements->pluck('ID')->all();
        $unlockStats = PlayerAchievement::query()
            ->whereIn('player_achievements.achievement_id', $achievementIds)
            ->whereHas('user', function ($query) { $query->tracked(); })
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
            $unlocksCount = $unlockCounts[$achievement->ID] ?? 0;
            $unlocksHardcoreCount = $hardcoreUnlockCounts[$achievement->ID] ?? 0;

            // force all unachieved to be 1
            $unlocksHardcoreCalc = $unlocksHardcoreCount ?: 1;
            $weight = 0.4;
            $pointsWeighted = (int) (
                $achievement->points * (1 - $weight)
                + $achievement->points * (($playersHardcoreCalc / $unlocksHardcoreCalc) * $weight)
            );

            $bulkUpdates[] = [
                'ID' => $achievement->ID,
                'unlocks_total' => $unlocksCount,
                'unlocks_hardcore_total' => $unlocksHardcoreCount,
                'unlock_percentage' => $playersTotal ? $unlocksCount / $playersTotal : 0,
                'unlock_hardcore_percentage' => $playersHardcore ? $unlocksHardcoreCount / $playersHardcore : 0,
                'TrueRatio' => $pointsWeighted,
            ];

            // Also update the model instance for the sum calculation later.
            $achievement->TrueRatio = $pointsWeighted;

            $searchIndexingService->queueAchievementForIndexing($achievement->ID);
        }

        if (!empty($bulkUpdates)) {
            $this->performBulkUpdate($bulkUpdates);
        }

        $game->TotalTruePoints = $achievements->sum('TrueRatio');
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
         * UPDATE Achievements
         * SET
         *   unlocks_total = CASE ID
         *     WHEN X THEN Y
         *   END,
         *   unlocks_hardcore_total = CASE ID
         *     WHEN X THEN Y
         *   END,
         *   unlock_percentage = CASE ID
         *     WHEN X THEN Y
         *   END,
         *   unlock_hardcore_percentage = CASE ID
         *     WHEN X THEN Y
         *   END,
         *   TrueRatio = CASE ID
         *     WHEN X THEN Y
         *   END,
         *   Updated = '...'
         * WHERE ID IN ( ... )
         */
        $ids = array_column($bulkUpdates, 'ID');
        $cases = [
            'unlocks_total' => 'CASE ID',
            'unlocks_hardcore_total' => 'CASE ID',
            'unlock_percentage' => 'CASE ID',
            'unlock_hardcore_percentage' => 'CASE ID',
            'TrueRatio' => 'CASE ID',
        ];

        foreach ($bulkUpdates as $update) {
            $cases['unlocks_total'] .= " WHEN {$update['ID']} THEN {$update['unlocks_total']}";
            $cases['unlocks_hardcore_total'] .= " WHEN {$update['ID']} THEN {$update['unlocks_hardcore_total']}";
            $cases['unlock_percentage'] .= " WHEN {$update['ID']} THEN {$update['unlock_percentage']}";
            $cases['unlock_hardcore_percentage'] .= " WHEN {$update['ID']} THEN {$update['unlock_hardcore_percentage']}";
            $cases['TrueRatio'] .= " WHEN {$update['ID']} THEN {$update['TrueRatio']}";
        }

        foreach ($cases as &$case) {
            $case .= ' END';
        }

        // Use DB to bypass model events.
        DB::table('Achievements')
            ->whereIn('ID', $ids)
            ->update([
                'unlocks_total' => DB::raw($cases['unlocks_total']),
                'unlocks_hardcore_total' => DB::raw($cases['unlocks_hardcore_total']),
                'unlock_percentage' => DB::raw($cases['unlock_percentage']),
                'unlock_hardcore_percentage' => DB::raw($cases['unlock_hardcore_percentage']),
                'TrueRatio' => DB::raw($cases['TrueRatio']),
                'Updated' => now(),
            ]);
    }
}
