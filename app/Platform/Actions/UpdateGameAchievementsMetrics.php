<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\Game;

class UpdateGameAchievementsMetrics
{
    public function execute(Game $game): void
    {

        // TODO refactor to do this for each achievement set

        $parentGameId = getParentGameIdFromGameId($game->id);
        if (config('feature.aggregate_queries')) {
            $parentGame = $parentGameId ? Game::find($parentGameId) : null;
            $playersTotal = $parentGame ? $parentGame->players_total : $game->players_total;
            $playersHardcore = $parentGame ? $parentGame->players_hardcore : $game->players_hardcore;
        } else {
            $playersTotal = getTotalUniquePlayers($game->id, $parentGameId);
            $playersHardcore = getTotalUniquePlayers($game->id, $parentGameId, null, true);
        }

        // force all unachieved to be 1
        $playersHardcoreCalc = $playersHardcore ?: 1;
        $pointsWeightedTotal = 0;
        $achievements = $game->achievements()->published()->get();
        foreach ($achievements as $achievement) {
            $unlocksCount = $achievement->playerAchievements()
                ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_achievements.user_id')
                ->where('user.Untracked', false)
                ->whereNull('user.Deleted')
                ->count();
            $unlocksHardcoreCount = $achievement->playerAchievements()
                ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_achievements.user_id')
                ->where('user.Untracked', false)
                ->whereNull('user.Deleted')
                ->whereNotNull('unlocked_hardcore_at')
                ->count();

            // force all unachieved to be 1
            $unlocksHardcoreCalc = $unlocksHardcoreCount ?: 1;
            $weight = 0.4;
            $pointsWeighted = (int) ($achievement->points * (1 - $weight)) + ($achievement->points * (($playersHardcoreCalc / $unlocksHardcoreCalc) * $weight));
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
