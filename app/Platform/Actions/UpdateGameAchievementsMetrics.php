<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

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
        foreach ($achievements as $achievement) {
            $unlocksCount = $achievement->playerAchievements()
                ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_achievements.user_id')
                ->where('user.Untracked', false)
                ->count();
            $unlocksHardcoreCount = $achievement->playerAchievements()
                ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_achievements.user_id')
                ->where('user.Untracked', false)
                ->whereNotNull('unlocked_hardcore_at')
                ->count();

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
