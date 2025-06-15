<?php

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\AchievementMaintainerUnlock;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Jobs\UpdateAchievementMetricsJob;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use App\Platform\Jobs\UpdateGamePlayerCountJob;
use App\Platform\Jobs\UpdatePlayerBeatenGamesStatsJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Support\Facades\DB;

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

        $affectedAchievements = legacyDbFetchAll("
            SELECT
                COALESCE(ua.display_name, ua.User) AS Author,
                ach.GameID,
                CASE WHEN pa.unlocked_hardcore_at THEN 1 ELSE 0 END AS HardcoreMode,
                COUNT(ach.ID) AS Count, SUM(ach.Points) AS Points,
                SUM(ach.TrueRatio) AS TruePoints
            FROM player_achievements pa
            INNER JOIN Achievements ach ON ach.ID = pa.achievement_id
            INNER JOIN UserAccounts ua ON ua.ID = ach.user_id
            WHERE ach.Flags = " . AchievementFlag::OfficialCore->value . "
            AND pa.user_id = {$user->id} $clause
            GROUP BY ach.user_id, ach.GameID, HardcoreMode
        ");

        $affectedGames = collect();
        $authorUsernames = collect();
        foreach ($affectedAchievements as $achievementData) {
            if ($achievementData['Author'] !== $user->User) {
                $authorUsernames->push($achievementData['Author']);
            }
            $affectedGames->push($achievementData['GameID']);
        }

        $maintainers = DB::select("
            SELECT DISTINCT COALESCE(ua.display_name, ua.User) AS Username
            FROM player_achievements pa
            INNER JOIN Achievements ach ON ach.ID = pa.achievement_id
            INNER JOIN achievement_maintainers m ON m.achievement_id = ach.ID
            INNER JOIN UserAccounts ua ON ua.ID = m.user_id
            WHERE ach.Flags = :flags
                AND pa.user_id = :user_id
                " . $clause . "
                AND COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) >= m.effective_from
                AND (
                    m.effective_until IS NULL 
                    OR COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) < m.effective_until
                )
                AND ua.ID != :user_id2
        ", [
            'flags' => AchievementFlag::OfficialCore->value,
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        foreach ($maintainers as $maintainer) {
            $authorUsernames->push($maintainer->Username);
        }

        if ($achievementID !== null) {
            $playerAchievement = $user->playerAchievements()->where('achievement_id', $achievementID)->first();
            if (!$playerAchievement) {
                // already deleted? do nothing.
                return;
            }

            $achievement = $playerAchievement->achievement;

            // Handle decrement for developer contribution credit before player_achievement deletion.
            if ($achievement->is_published) {
                // Check if there's a maintainer unlock record.
                $maintainerUnlock = AchievementMaintainerUnlock::query()
                    ->where('player_achievement_id', $playerAchievement->id)
                    ->first();

                if ($maintainerUnlock) {
                    // Credit was given to the maintainer.
                    $developer = User::find($maintainerUnlock->maintainer_id);
                } else {
                    // Credit was given to the original author.
                    $developer = $achievement->developer;
                }

                if ($developer && $developer->id !== $user->id && !$developer->trashed()) {
                    // Perform a quick incremental decrement.
                    // For resets, we don't need to worry about the
                    // isHardcore flag since we're removing the unlock entirely.
                    (new IncrementDeveloperContributionYieldAction())->execute(
                        $developer,
                        $achievement,
                        $playerAchievement,
                        isUnlock: false
                    );
                }
            }

            // Delete any maintainer unlock records related to this player_achievement entity.
            AchievementMaintainerUnlock::where('player_achievement_id', $playerAchievement->id)->delete();

            if ($achievement->is_published) {
                // resetting a published achievement removes the completion/mastery badge.
                // RevalidateAchievementSetBadgeEligibilityAction will be called indirectly
                // from the UpdatePlayerGameMetricsJob, but it does not revoke badges unless
                // all achievements for a game are reset.
                $playerBadge = $user->playerBadges()
                    ->where('AwardType', AwardType::Mastery)
                    ->where('AwardData', $achievement->game_id)
                    ->where('AwardDataExtra', $playerAchievement->unlocked_hardcore_at ? UnlockMode::Hardcore : UnlockMode::Softcore)
                    ->first();
                if ($playerBadge) {
                    PlayerBadgeLost::dispatch($user, $playerBadge->AwardType, $playerBadge->AwardData, $playerBadge->AwardDataExtra);
                    $playerBadge->delete();
                }
            }
            $playerAchievement->delete();
        } elseif ($gameID !== null) {
            $achievementIds = Achievement::where('GameID', $gameID)->pluck('ID');

            // Delete any maintainer unlock records related to these player_achievement entities.
            $playerAchievementIds = $user->playerAchievements()->whereIn('achievement_id', $achievementIds)->pluck('id');
            if (!$playerAchievementIds->isEmpty()) {
                AchievementMaintainerUnlock::whereIn('player_achievement_id', $playerAchievementIds)->delete();
            }

            $user->playerAchievements()
                ->whereIn('achievement_id', $achievementIds)
                ->delete();
        } else {
            // Delete all maintainer unlock records related to these player_achievement entities.
            AchievementMaintainerUnlock::query()
                ->whereIn('player_achievement_id', function ($query) use ($user) {
                    $query->select('id')->from('player_achievements')->where('user_id', $user->id);
                })
                ->delete();

            // fulfill deletion request
            $user->playerGames()->forceDelete();
            $user->playerBadges()->delete();
            $user->playerAchievements()->delete();

            $user->RAPoints = 0;
            $user->RASoftcorePoints = null;
            $user->TrueRAPoints = null;
            $user->ContribCount = 0;
            $user->ContribYield = 0;
            $user->saveQuietly();
        }

        // For game-wide or full resets, we need to do full recalculation of affected dev stats.
        // For single achievement resets, we've already handled it incrementally above.
        if ($achievementID === null) {
            $authors = User::query()
                ->where(function ($query) use ($authorUsernames) {
                    $query->whereIn('User', $authorUsernames->unique())
                        ->orWhereIn('display_name', $authorUsernames->unique());
                })
                ->get('ID');
            foreach ($authors as $author) {
                dispatch(new UpdateDeveloperContributionYieldJob($author->id));
            }
        }

        $isFullReset = $achievementID === null && $gameID === null;
        $affectedGames = $affectedGames->unique();
        foreach ($affectedGames as $affectedGameID) {
            if (!$isFullReset) {
                // update the player game metrics. if the player's unlocked achievement
                // count drops to zero, that will trigger an UpdateGamePlayerCountJob.
                dispatch(new UpdatePlayerGameMetricsJob($user->id, $affectedGameID));
            } else {
                // if doing a full reset, explicitly update the player count for the game
                // as the player games records were deleted and thus implicitly zeroed out.
                dispatch(new UpdateGamePlayerCountJob($affectedGameID))->onQueue('game-player-count');
            }
        }

        if ($achievementID !== null) {
            // when resetting a single achievement, the player count won't change, so the
            // metrics for each achievement won't be recalculated. we really only need to
            // recalculate the affected achievement anyway, so do that now.
            dispatch(new UpdateAchievementMetricsJob($achievementID))->onQueue('achievement-metrics');
        }

        dispatch(new UpdatePlayerBeatenGamesStatsJob($user->id));

        $user->saveQuietly();
    }
}
