<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Platform\Services\SearchIndexingService;
use Illuminate\Support\Facades\DB;

class UpdateGameAchievementsMetricsAction
{
    public function execute(Game $game): void
    {
        // TODO refactor to do this for each achievement set

        // NOTE if game has a parent game it contains the parent game's players metrics
        $playersTotal = $game->players_total;
        $playersHardcore = $game->players_hardcore;

        // If players_total is 0, calculate from actual unlocks
        // This handles cases where metrics are updated before game player counts (ie: tests)
        if ($playersTotal === 0) {
            $playerCounts = PlayerAchievement::query()
                ->whereIn('achievement_id', $game->achievements()->published()->pluck('ID'))
                ->whereHas('user', function ($query) { $query->tracked(); })
                ->selectRaw('COUNT(DISTINCT user_id) as total_players')
                ->selectRaw('COUNT(DISTINCT CASE WHEN unlocked_hardcore_at IS NOT NULL THEN user_id END) as hardcore_players')
                ->first();

            $playersTotal = $playerCounts->total_players ?? 0;
            $playersHardcore = $playerCounts->hardcore_players ?? 0;
        }

        // force all unachieved to be 1
        $playersHardcoreCalc = $playersHardcore ?: 1;
        $pointsWeightedTotal = 0;
        $achievements = $game->achievements()->published()->get();
        if ($achievements->isEmpty()) {
            return;
        }

        // ensure original values are loaded for comparison / dirty checking
        $achievements->each(function ($achievement) {
            $achievement->syncOriginal();
        });

        $achievementIds = $achievements->pluck('ID')->all();

        // Get both total and hardcore counts in a single query.
        $unlockStats = PlayerAchievement::query()
            ->whereIn('player_achievements.achievement_id', $achievementIds)
            ->whereHas('user', function ($query) { $query->tracked(); })
            ->groupBy('player_achievements.achievement_id')
            ->selectRaw(<<<SQL
                player_achievements.achievement_id,
                COUNT(*) as total_unlocks,
                SUM(CASE WHEN unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) as hardcore_unlocks
            SQL)
            ->get();

        // Convert to lookup arrays for faster read access.
        $unlockCounts = [];
        $hardcoreUnlockCounts = [];
        foreach ($unlockStats as $stat) {
            $unlockCounts[$stat->achievement_id] = $stat->total_unlocks;
            $hardcoreUnlockCounts[$stat->achievement_id] = $stat->hardcore_unlocks;
        }

        $searchIndexingService = app()->make(SearchIndexingService::class);

        $pointsWeightedTotal = 0;
        $achievementUpdates = [];

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
            $pointsWeightedTotal += $pointsWeighted;

            // Round percentages to 9 decimal places to match the exact database column precision (decimal(10,9)).
            // This prevents unnecessary updates due to precision differences in PHP.
            $unlockPercentage = round($playersTotal ? $unlocksCount / $playersTotal : 0, 9);
            $unlockHardcorePercentage = round($playersHardcore ? $unlocksHardcoreCount / $playersHardcore : 0, 9);

            $achievement->unlocks_total = $unlocksCount;
            $achievement->unlocks_hardcore_total = $unlocksHardcoreCount;
            $achievement->unlock_percentage = $unlockPercentage;
            $achievement->unlock_hardcore_percentage = $unlockHardcorePercentage;
            $achievement->TrueRatio = $pointsWeighted;

            // Only update the achievement if values have actually changed.
            $isDirty =
                $achievement->unlocks_total !== $achievement->getOriginal('unlocks_total')
                || $achievement->unlocks_hardcore_total !== $achievement->getOriginal('unlocks_hardcore_total')
                || (float) $achievement->unlock_percentage !== (float) $achievement->getOriginal('unlock_percentage')
                || (float) $achievement->unlock_hardcore_percentage !== (float) $achievement->getOriginal('unlock_hardcore_percentage')
                || $achievement->TrueRatio !== $achievement->getOriginal('TrueRatio');

            // If the achievement is truly dirty, add it to our list of batch updates.
            if ($isDirty) {
                $achievementUpdates[] = [
                    'ID' => $achievement->id,
                    'unlocks_total' => $unlocksCount,
                    'unlocks_hardcore_total' => $unlocksHardcoreCount,
                    'unlock_percentage' => $unlockPercentage,
                    'unlock_hardcore_percentage' => $unlockHardcorePercentage,
                    'TrueRatio' => $pointsWeighted,
                    'DateModified' => now(),
                ];

                // Reindex the achievement in Meilisearch.
                $searchIndexingService->queueAchievementForIndexing($achievement->id);
            }
        }

        // Batch update all achievements at once to prevent N+1 writes.
        if (!empty($achievementUpdates)) {
            $this->batchUpdateAchievements($achievementUpdates);
        }

        $game->TotalTruePoints = $pointsWeightedTotal;

        $game->saveQuietly();
        $searchIndexingService->queueGameForIndexing($game->id);

        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }

    private function batchUpdateAchievements(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        /**
         * Eloquent does not allow us to use `upsert()` for this, as `upsert()` requires
         * every single field in the model to be passed in. This uses too much memory.
         * Performance matters here - we'll use the DB facade and raw SQL instead.
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
         *   DateModified = "..."
         * WHERE
         *   ID IN ( ... );
         */
        $ids = array_column($updates, 'ID');

        $sql = "UPDATE Achievements SET ";
        $bindings = [];

        // Build CASE statements with parameter binding.
        $fields = [
            'unlocks_total',
            'unlocks_hardcore_total',
            'unlock_percentage',
            'unlock_hardcore_percentage',
            'TrueRatio',
        ];

        foreach ($fields as $i => $field) {
            if ($i > 0) {
                $sql .= ", ";
            }
            $sql .= "{$field} = CASE ID ";

            foreach ($updates as $update) {
                $sql .= "WHEN ? THEN ? ";
                $bindings[] = $update['ID'];
                $bindings[] = $update[$field];
            }

            $sql .= "END";
        }

        // Add DateModified.
        $sql .= ", DateModified = ? ";
        $bindings[] = now();

        // Add the WHERE clause.
        $sql .= "WHERE ID IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        foreach ($ids as $id) {
            $bindings[] = $id;
        }

        DB::statement($sql, $bindings);
    }
}
