<?php

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use App\Platform\Models\Achievement;
use App\Site\Models\User;

class ResetPlayerProgress
{
    public function execute(User $user, ?int $achievementID = null, ?int $gameID = null): void
    {
        $clause = '';
        if ($achievementID !== null) {
            $clause = "AND ach.ID=$achievementID";
        } elseif ($gameID !== null) {
            $clause = "AND ach.GameID=$gameID";
        }

        $affectedAchievements = legacyDbFetchAll("
            SELECT
                ach.Author,
                ach.GameID,
                CASE WHEN pa.unlocked_hardcore_at THEN 1 ELSE 0 END AS HardcoreMode,
                COUNT(ach.ID) AS Count, SUM(ach.Points) AS Points,
                SUM(ach.TrueRatio) AS TruePoints
            FROM player_achievements pa
            INNER JOIN Achievements ach ON ach.ID = pa.achievement_id
            WHERE ach.Flags = " . AchievementFlag::OfficialCore . "
            AND pa.user_id = {$user->id} $clause
            GROUP BY ach.Author, ach.GameID, HardcoreMode
        ");

        $affectedGames = collect();
        $authorUsernames = collect();
        foreach ($affectedAchievements as $achievementData) {
            if ($achievementData['Author'] !== $user->User) {
                $authorUsernames->push($achievementData['Author']);
            }
            $affectedGames->push($achievementData['GameID']);
        }

        if ($achievementID !== null) {
            $playerAchievement = $user->playerAchievements()->where('achievement_id', $achievementID)->first();
            $achievement = $playerAchievement->achievement;
            if ($playerAchievement->unlocked_hardcore_at && $achievement->isPublished) {
                // resetting a hardcore unlock removes hardcore mastery badges
                $user->playerBadges()
                    ->where('AwardType', AwardType::Mastery)
                    ->where('AwardData', $achievement->game_id)
                    ->where('AwardDataExtra', UnlockMode::Hardcore)
                    ->delete();
            }
            $playerAchievement->delete();

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
            // fulfill deletion request
            $user->playerGames()->forceDelete();
            $user->playerBadges()->delete();
            $user->playerAchievements()->delete();
            $user->playerAchievementsLegacy()->delete();

            $user->RAPoints = 0;
            $user->RASoftcorePoints = null;
            $user->TrueRAPoints = null;
            $user->ContribCount = 0;
            $user->ContribYield = 0;
            $user->save();
        }

        // TODO
        // $authors = User::whereIn('User', $authorUsernames->unique())->get('ID');
        // foreach ($authors as $author) {
        //     dispatch(new UpdateDeveloperContributionYieldJob($author->id));
        // }

        $isFullReset = $achievementID === null && $gameID === null;
        $affectedGames = $affectedGames->unique();
        foreach ($affectedGames as $affectedGameID) {
            // no use updating deleted player games if it's a full reset
            if (!$isFullReset) {
                dispatch(new UpdatePlayerGameMetricsJob($user->id, $affectedGameID));
            }

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
