<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\Game;

class UpdateGameAchievementsMetrics
{
    public function execute(Game $game): void
    {

        // TODO refactor to do this for each achievement set

        // force all unachieved to be 1
        $playersHardcoreCalc = $game->players_hardcore ?: 1;
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
            $pointsWeighted = (int) ($achievement->points * (1 - $weight)) + ($achievement->points * (($playersHardcoreCalc / $unlocksHardcoreCalc) * $weight));
            $pointsWeightedTotal += $pointsWeighted;

            $achievement->unlocks_total = $unlocksCount;
            $achievement->unlocks_hardcore_total = $unlocksHardcoreCount;
            $achievement->unlock_percentage = $game->players_total ? $unlocksCount / $game->players_total : 0;
            $achievement->unlock_hardcore_percentage = $game->players_hardcore ? $unlocksHardcoreCount / $game->players_hardcore : 0;
            $achievement->TrueRatio = $pointsWeighted;
            $achievement->save();
        }

        $game->TotalTruePoints = $pointsWeightedTotal;
        $game->save();

        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }
}
