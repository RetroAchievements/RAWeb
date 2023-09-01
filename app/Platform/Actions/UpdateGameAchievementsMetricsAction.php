<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;

class UpdateGameAchievementsMetricsAction
{
    public function execute(Game $game): void
    {
        // TODO refactor to do this for each achievement set

        $gameId = (int) $game->id;

        $parentGameId = getParentGameIdFromGameId($gameId);

        // TODO refactor to player_achievements

        $achievementsData = legacyDbFetchAll("SELECT ach.ID, ach.Points, SUM(CASE WHEN NOT ua.Untracked THEN aw.HardcoreMode ELSE 0 END) AS UnlocksHardcore
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE ach.GameID = $game->id
              AND ach.Flags = " . AchievementFlag::OfficialCore . "
              GROUP BY ach.ID");

        if ($achievementsData->isEmpty()) {
            return;
        }

        // TODO use $game->players_hardcore
        $playersHardcore = getTotalUniquePlayers($gameId, $parentGameId, null, true);

        $playersHardcoreCalc = $playersHardcore;
        if ($playersHardcoreCalc == 0) { // force all unachieved to be 1
            $playersHardcoreCalc = 1;
        }

        $pointsWeightedTotal = 0;
        foreach ($achievementsData as $achievementData) {
            $achievementId = $achievementData['ID'];
            $achievementPoints = (int) $achievementData['Points'];
            $unlocksHardcore = (int) $achievementData['UnlocksHardcore'];

            $unlocksHardcoreCalc = $unlocksHardcore;
            if ($unlocksHardcoreCalc == 0) { // force all unachieved to be 1
                $unlocksHardcoreCalc = 1;
            }

            $weight = 0.4;
            $pointsWeighted = (int) ($achievementPoints * (1 - $weight)) + ($achievementPoints * (($playersHardcoreCalc / $unlocksHardcoreCalc) * $weight));
            $pointsWeightedTotal += $pointsWeighted;

            $achievement = Achievement::find($achievementId);
            $achievement->unlocks_total = 0;
            $achievement->unlocks_hardcore_total = $unlocksHardcore;
            $achievement->unlock_percentage = 0;
            $achievement->unlock_hardcore_percentage = $unlocksHardcore ? $unlocksHardcore / $playersHardcore : 0;
            $achievement->TrueRatio = $pointsWeighted;
            $achievement->save();
        }

        $game->TotalTruePoints = $pointsWeightedTotal;
        $game->save();

        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }
}
