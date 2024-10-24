<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use Illuminate\Support\Facades\DB;

class UpdateGameAchievementsMetrics
{
    public function execute(Game $game): void
    {
        // TODO refactor to do this for each achievement set

        // NOTE if game has a parent game it contains the parent game's players metrics
        $playersTotal = $game->players_total;
        $playersHardcore = $game->players_hardcore;

        // force all unachieved to be 1
        $playersHardcoreCalc = $playersHardcore ?: 1;
        $pointsWeightedTotal = 0;
        $achievements = $game->achievements()->published()->get();
        if ($achievements->isEmpty()) {
            return;
        }

        $achievementIds = $achievements->pluck('ID')->all();

        // Get both total and hardcore counts in a single query.
        $unlockStats = DB::table('player_achievements as pa')
            ->leftJoin('UserAccounts as user', 'user.ID', '=', 'pa.user_id')
            ->whereIn('pa.achievement_id', $achievementIds)
            ->where('user.Untracked', false)
            ->groupBy('pa.achievement_id')
            ->select([
                'pa.achievement_id',
                DB::raw('COUNT(*) as total_unlocks'),
                DB::raw('SUM(CASE WHEN unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) as hardcore_unlocks'),
            ])
            ->get();

        // Convert to lookup arrays for faster read access.
        $unlockCounts = [];
        $hardcoreUnlockCounts = [];
        foreach ($unlockStats as $stat) {
            $unlockCounts[$stat->achievement_id] = $stat->total_unlocks;
            $hardcoreUnlockCounts[$stat->achievement_id] = $stat->hardcore_unlocks;
        }

        $pointsWeightedTotal = 0;

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

            $achievement->unlocks_total = $unlocksCount;
            $achievement->unlocks_hardcore_total = $unlocksHardcoreCount;
            $achievement->unlock_percentage = $playersTotal ? $unlocksCount / $playersTotal : 0;
            $achievement->unlock_hardcore_percentage = $playersHardcore ? $unlocksHardcoreCount / $playersHardcore : 0;
            $achievement->TrueRatio = $pointsWeighted;
            $achievement->save();
        }

        $game->TotalTruePoints = $pointsWeightedTotal;
        $game->save();

        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }
}
