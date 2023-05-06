<?php

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementType;
use App\Site\Models\User;

class ResetPlayerAchievementAction
{
    public function execute(User $user, ?int $achievementID = null, ?int $gameID = null): bool
    {
        $clause = '';
        if ($achievementID !== null) {
            $clause = "AND ach.ID=$achievementID";
        } elseif ($gameID !== null) {
            $clause = "AND ach.GameID=$gameID";
        }

        $query = "SELECT ach.Author, ach.GameID, aw_ach.HardcoreMode,
                         COUNT(ach.ID) AS Count, SUM(ach.Points) AS Points,
                         SUM(ach.TrueRatio) AS TruePoints
                  FROM (
                     SELECT aw.AchievementID, MAX(aw.HardcoreMode) as HardcoreMode FROM
                     Awarded aw LEFT JOIN Achievements ach ON ach.ID=aw.AchievementID
                     WHERE aw.User = :username $clause GROUP BY aw.AchievementID
                  ) as aw_ach
                  LEFT JOIN Achievements ach ON ach.ID = aw_ach.AchievementID
                  WHERE ach.Flags = " . AchievementType::OfficialCore . "
                  GROUP BY ach.Author, ach.GameID, aw_ach.HardcoreMode";

        $affectedGames = [];
        foreach (legacyDbFetchAll($query, ['username' => $user->User]) as $row) {
            if ($row['HardcoreMode']) {
                $user->RAPoints -= $row['Points'];
                $user->TrueRAPoints -= $row['TruePoints'];
            } else {
                $user->RASoftcorePoints -= $row['Points'];
            }

            if ($row['Author'] !== $user->User) {
                attributeDevelopmentAuthor($row['Author'], -$row['Count'], -$row['Points']);
            }

            if (!in_array($row['GameID'], $affectedGames)) {
                $affectedGames[] = $row['GameID'];
            }
        }

        $clause = '';
        if ($achievementID !== null) {
            $clause = "AND AchievementID=$achievementID";
        } elseif ($gameID !== null) {
            $clause = "AND AchievementID IN (SELECT ID FROM Achievements WHERE GameID=$gameID)";
        }

        legacyDbStatement("DELETE FROM Awarded WHERE User = :username $clause", ['username' => $user->User]);

        foreach ($affectedGames as $affectedGameID) {
            // delete the mastery badge (if the player had it)
            $user->playerBadges()
                ->where('AwardType', AwardType::Mastery)
                ->where('AwardData', $affectedGameID)
                ->delete();

            // force the top achievers for the game to be recalculated
            expireGameTopAchievers($affectedGameID);

            // expire the cached unlocks for the game for the user
            expireUserAchievementUnlocksForGame($user->User, $affectedGameID);
        }

        $user->save();

        return true;
    }
}
