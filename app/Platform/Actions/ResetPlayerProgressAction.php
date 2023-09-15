<?php

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Achievement;
use App\Site\Models\User;

class ResetPlayerProgressAction
{
    public function execute(User $user, ?int $achievementID = null, ?int $gameID = null): void
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
                  WHERE ach.Flags = " . AchievementFlag::OfficialCore . "
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
            $user->playerAchievements()->where('achievement_id', $achievementID)->delete();
            // TODO one achievement reset PlayerAchievementLocked::dispatch($user, $achievementID);

            $clause = "AND AchievementID=$achievementID";
        } elseif ($gameID !== null) {
            $user->playerAchievements()
                ->whereIn('achievement_id', Achievement::where('GameID', $gameID)->pluck('ID'))
                ->delete();
            // TODO one game reset PlayerGameProgressReset::dispatch($user, $gameID);

            $clause = "AND AchievementID IN (SELECT ID FROM Achievements WHERE GameID=$gameID)";
        } else {
            $user->playerAchievements()->delete();
            // TODO multiple games reset PlayerGameProgressReset::dispatch($user, $gameIDs);
        }

        legacyDbStatement("DELETE FROM Awarded WHERE User = :username $clause", ['username' => $user->User]);

        // expire the cached awarded data for the user's profile
        // TODO: Remove when denormalized data is ready.
        expireUserCompletedGamesCacheValue($user->User);

        // TODO everything below should be queued based on the events dispatched above
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

            // revoke beaten game awards if necessary
            testBeatenGame($affectedGameID, $user->User, false);
        }

        $user->save();
    }
}
