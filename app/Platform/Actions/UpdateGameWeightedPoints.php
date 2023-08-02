<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Enums\AchievementFlag;

class UpdateGameWeightedPoints
{
    public function run(int $gameId): bool
    {
        // TODO pass game model

        $parentGameId = getParentGameIdFromGameId($gameId);

        $query = "SELECT ach.ID, ach.Points, SUM(CASE WHEN NOT ua.Untracked THEN aw.HardcoreMode ELSE 0 END) AS NumAchieved
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE ach.GameID = $gameId
              AND ach.Flags = " . AchievementFlag::OfficialCore . "
              GROUP BY ach.ID";

        $dbResult = s_mysql_query($query);

        if ($dbResult !== false) {
            $numHardcoreWinners = getTotalUniquePlayers((int) $gameId, $parentGameId, null, true, AchievementFlag::OfficialCore);

            if ($numHardcoreWinners == 0) { // force all unachieved to be 1
                $numHardcoreWinners = 1;
            }

            $ratioTotal = 0;
            while ($nextData = mysqli_fetch_assoc($dbResult)) {
                $achID = $nextData['ID'];
                $achPoints = (int) $nextData['Points'];
                $numAchieved = (int) $nextData['NumAchieved'];

                if ($numAchieved == 0) { // force all unachieved to be 1
                    $numAchieved = 1;
                }

                $ratioFactor = 0.4;
                $newTrueRatio = ($achPoints * (1.0 - $ratioFactor)) + ($achPoints * (($numHardcoreWinners / $numAchieved) * $ratioFactor));
                $trueRatio = (int) $newTrueRatio;
                $ratioTotal += $trueRatio;

                $query = "UPDATE Achievements AS ach
                      SET ach.TrueRatio = $trueRatio
                      WHERE ach.ID = $achID";
                s_mysql_query($query);
            }

            $query = "UPDATE GameData AS gd
                  SET gd.TotalTruePoints = $ratioTotal
                  WHERE gd.ID = $gameId";
            s_mysql_query($query);

            return true;
        }

        return false;
    }
}
