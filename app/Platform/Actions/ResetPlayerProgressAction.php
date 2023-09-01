<?php

namespace App\Platform\Actions;

use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
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

        // TODO refactor, do not use Awarded
        $affectedAchievements = legacyDbFetchAll("
            SELECT
                ach.Author,
                ach.GameID,
                aw_ach.HardcoreMode,
                COUNT(ach.ID) AS Count, SUM(ach.Points) AS Points,
                SUM(ach.TrueRatio) AS TruePoints
            FROM (
                SELECT aw.AchievementID, MAX(aw.HardcoreMode) as HardcoreMode
                FROM Awarded aw
                LEFT JOIN Achievements ach ON ach.ID=aw.AchievementID
                WHERE aw.User = :username $clause GROUP BY aw.AchievementID
            ) as aw_ach
            LEFT JOIN Achievements ach ON ach.ID = aw_ach.AchievementID
            WHERE ach.Flags = " . AchievementFlag::OfficialCore . "
            GROUP BY ach.Author, ach.GameID, aw_ach.HardcoreMode
        ", ['username' => $user->User]);

        $affectedGames = collect();
        $authorUsernames = collect();
        foreach ($affectedAchievements as $achievementData) {
            if ($achievementData['Author'] !== $user->User) {
                $authorUsernames->push($achievementData['Author']);
            }
            $affectedGames->push($achievementData['GameID']);
        }

        if ($achievementID !== null) {
            $user->playerAchievements()->where('achievement_id', $achievementID)->delete();
            $user->playerAchievementsLegacy()->where('AchievementID', $achievementID)->delete();
        } elseif ($gameID !== null) {
            $achievementIds = Achievement::where('GameID', $gameID)->pluck('ID');

            $user->playerAchievements()
                ->whereIn('achievement_id', $achievementIds)
                ->delete();

            $user->playerAchievementsLegacy()
                ->whereIn('AchievementID', $achievementIds)
                ->delete();
        } else {
            $user->playerAchievements()->delete();
            $user->playerAchievementsLegacy()->delete();
        }

        $authors = User::whereIn('User', $authorUsernames->unique())->get('ID');
        foreach ($authors as $author) {
            dispatch(new UpdateDeveloperContributionYieldJob($author->id));
        }

        $affectedGames = $affectedGames->unique();
        foreach ($affectedGames as $affectedGameID) {
            dispatch(new UpdatePlayerGameMetricsJob($user->id, $affectedGameID));

            // force the top achievers for the game to be recalculated
            expireGameTopAchievers($affectedGameID);

            // expire the cached unlocks for the game for the user
            expireUserAchievementUnlocksForGame($user->User, $affectedGameID);

            // expire the cached awarded data for the user's profile
            // TODO: Remove when denormalized data is ready.
            expireUserCompletedGamesCacheValue($user->User);
        }

        $user->save();
    }
}
