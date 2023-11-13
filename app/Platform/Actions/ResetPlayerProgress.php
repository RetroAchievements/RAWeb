<?php

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use App\Platform\Jobs\UpdateGameMetricsJob;
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
            if ($achievement->isPublished) {
                // resetting a published achievement removes the completion/mastery badge.
                // RevalidateAchievementSetBadgeEligibility will be called indirectly from
                // the UpdatePlayerGameMetricsJob, but it does not revoke badges unless all
                // achievements for a game are reset.
                $playerBadge = $user->playerBadges()
                    ->where('AwardType', AwardType::Mastery)
                    ->where('AwardData', $achievement->game_id)
                    ->where('AwardDataExtra', $playerAchievement->unlocked_hardcore_at ? UnlockMode::Hardcore : UnlockMode::Softcore)
                    ->first();
                if ($playerBadge) {
                    PlayerBadgeLost::dispatch($playerBadge);
                    $playerBadge->delete();
                }
            }
            $playerAchievement->delete();
        } elseif ($gameID !== null) {
            $achievementIds = Achievement::where('GameID', $gameID)->pluck('ID');

            $user->playerAchievements()
                ->whereIn('achievement_id', $achievementIds)
                ->delete();
        } else {
            // fulfill deletion request
            $user->playerGames()->forceDelete();
            $user->playerBadges()->delete();
            $user->playerAchievements()->delete();

            $user->RAPoints = 0;
            $user->RASoftcorePoints = null;
            $user->TrueRAPoints = null;
            $user->ContribCount = 0;
            $user->ContribYield = 0;
            $user->save();
        }

        $authors = User::whereIn('User', $authorUsernames->unique())->get('ID');
        foreach ($authors as $author) {
            dispatch(new UpdateDeveloperContributionYieldJob($author->id));
        }

        $isFullReset = $achievementID === null && $gameID === null;
        $affectedGames = $affectedGames->unique();
        foreach ($affectedGames as $affectedGameID) {
            if (!$isFullReset) {
                // update the player game metrics, which will cascade into the game metrics
                dispatch(new UpdatePlayerGameMetricsJob($user->id, $affectedGameID));
            } else {
                // update the game metrics directly
                dispatch(new UpdateGameMetricsJob($affectedGameID));
            }
        }

        $user->save();
    }
}
