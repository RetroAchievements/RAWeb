<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Platform\Services\SearchIndexingService;
use Illuminate\Support\Collection;

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

            $achievement->unlocks_total = $unlocksCount;
            $achievement->unlocks_hardcore_total = $unlocksHardcoreCount;
            $achievement->unlock_percentage = $playersTotal ? $unlocksCount / $playersTotal : 0;
            $achievement->unlock_hardcore_percentage = $playersHardcore ? $unlocksHardcoreCount / $playersHardcore : 0;
            $achievement->TrueRatio = $pointsWeighted;

            $achievement->saveQuietly();
            $searchIndexingService->queueAchievementForIndexing($achievement->ID);
        }
    }
}
