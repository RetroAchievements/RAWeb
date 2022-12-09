<?php

namespace LegacyApp\Platform\Actions;

use LegacyApp\Platform\Enums\AchievementType;

class UpdateGameWeightedPoints
{
    public function run(int $gameId): bool
    {
        sanitize_sql_inputs($gameID);

        $query = "SELECT ach.ID, ach.Points, COUNT(aw.HardcoreMode) AS NumAchieved
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.HardcoreMode = 1
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User AND NOT ua.Untracked
              WHERE ach.GameID = $gameID AND ach.Flags = " . AchievementType::OfficialCore . "
              GROUP BY ach.ID";

        $dbResult = s_mysql_query($query);

        if ($dbResult !== false) {
            $numHardcoreWinners = getTotalUniquePlayers($gameID, null, true, AchievementType::OfficialCore);

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
                  WHERE gd.ID = $gameID";
            s_mysql_query($query);

            // RECALCULATED " . count($achData) . " achievements for game ID $gameID ($ratioTotal)"

            return true;
        }

        return false;
    }
}
