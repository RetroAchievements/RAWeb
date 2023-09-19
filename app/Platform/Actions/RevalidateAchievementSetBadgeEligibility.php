<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ActivityType;
use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerGameBeaten;
use App\Platform\Events\PlayerGameCompleted;
use App\Platform\Models\PlayerGame;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevalidateAchievementSetBadgeEligibility
{
    public function execute(PlayerGame $playerGame): void
    {
        // TODO do this for each player_achievement_set as soon as achievement set separation is introduced

        if (!$playerGame->user) {
            return;
        }

        $this->revalidateBeatenBadgeEligibility($playerGame);
        $this->revalidateCompletionBadgeEligibility($playerGame);
    }

    private function revalidateBeatenBadgeEligibility(PlayerGame $playerGame): void
    {
        $badge = $playerGame->user->playerBadges()
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $playerGame->game->id);
        $softcoreBadge = $badge->where('AwardDataExtra', UnlockMode::Softcore);
        $hardcoreBadge = $badge->where('AwardDataExtra', UnlockMode::Hardcore);

        if ($playerGame->beaten_at === null && $softcoreBadge->exists()) {
            PlayerBadgeLost::dispatch($softcoreBadge->first());
            $softcoreBadge->delete();
        }

        if ($playerGame->beaten_hardcore_at === null && $hardcoreBadge->exists()) {
            PlayerBadgeLost::dispatch($hardcoreBadge->first());
            $hardcoreBadge->delete();
        }

        if ($playerGame->beaten_at !== null && !$softcoreBadge->exists()) {
            $badge = AddSiteAward(
                $playerGame->user->username,
                AwardType::GameBeaten,
                $playerGame->game->id,
                UnlockMode::Softcore,
                $playerGame->beaten_at,
                displayOrder: 0
            );
            PlayerBadgeAwarded::dispatch($badge);
            PlayerGameBeaten::dispatch($playerGame->user, $playerGame->game);
        }

        if ($playerGame->beaten_hardcore_at !== null && !$hardcoreBadge->exists()) {
            $badge = AddSiteAward(
                $playerGame->user->username,
                AwardType::GameBeaten,
                $playerGame->game->id,
                UnlockMode::Hardcore,
                $playerGame->beaten_hardcore_at,
                displayOrder: 0
            );
            PlayerBadgeAwarded::dispatch($badge);
            PlayerGameBeaten::dispatch($playerGame->user, $playerGame->game, true);

            if ($playerGame->beaten_hardcore_at->gte(Carbon::now()->subMinutes(10))) {
                static_addnewhardcoregamebeaten($playerGame->game->id, $playerGame->user->username);
            }
        }
    }

    private function revalidateCompletionBadgeEligibility(PlayerGame $playerGame): void
    {
        $badge = $playerGame->user->playerBadges()
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardData', $playerGame->game->id);
        $softcoreBadge = $badge->where('AwardDataExtra', UnlockMode::Softcore);
        $hardcoreBadge = $badge->where('AwardDataExtra', UnlockMode::Hardcore);

        if ($playerGame->completed_at === null && $softcoreBadge->exists()) {
            PlayerBadgeLost::dispatch($softcoreBadge->first());
            $softcoreBadge->delete();
        }

        if ($playerGame->completed_hardcore_at === null && $hardcoreBadge->exists()) {
            PlayerBadgeLost::dispatch($hardcoreBadge->first());
            $hardcoreBadge->delete();
        }

        if ($playerGame->completed_at !== null && !$softcoreBadge->exists()) {
            $badge = AddSiteAward(
                $playerGame->user->username,
                AwardType::Mastery,
                $playerGame->game->id,
                UnlockMode::Softcore,
                $playerGame->completed_at,
                displayOrder: 0
            );
            PlayerBadgeAwarded::dispatch($badge);
            PlayerGameCompleted::dispatch($playerGame->user, $playerGame->game);

            // TODO WriteUserActivity
            $recentActivity = $playerGame->user->legacyActivities()
                ->where('activitytype', ActivityType::CompleteGame)
                ->where('data', $playerGame->game->id)
                ->where('data2', UnlockMode::Softcore)
                ->where('lastupdate', '>=', Carbon::now()->subHour())
                ->first();
            if ($recentActivity === null) {
                postActivity($playerGame->user->username, ActivityType::CompleteGame, $playerGame->game->id, UnlockMode::Softcore);
            }
        }

        if ($playerGame->completed_hardcore_at !== null && !$hardcoreBadge->exists()) {
            $badge = AddSiteAward(
                $playerGame->user->username,
                AwardType::Mastery,
                $playerGame->game->id,
                UnlockMode::Hardcore,
                $playerGame->completed_hardcore_at,
                displayOrder: 0
            );
            PlayerBadgeAwarded::dispatch($badge);
            PlayerGameCompleted::dispatch($playerGame->user, $playerGame->game, true);

            // TODO WriteUserActivity
            $recentActivity = $playerGame->user->legacyActivities()
                ->where('activitytype', ActivityType::CompleteGame)
                ->where('data', $playerGame->game->id)
                ->where('data2', UnlockMode::Hardcore)
                ->where('lastupdate', '>=', Carbon::now()->subHour())
                ->first();
            if ($recentActivity === null) {
                postActivity($playerGame->user->username, ActivityType::CompleteGame, $playerGame->game->id, UnlockMode::Hardcore);
            }

            if ($playerGame->completed_hardcore_at->gte(Carbon::now()->subMinutes(10))) {
                static_addnewhardcoremastery($playerGame->game->id, $playerGame->user->username);
            }
        }
    }

    // private function __()
    // {
    //     // get all mastery awards for the user
    //     // TODO use PlayerBadge model
    //     $awards = DB::table('SiteAwards')
    //         ->where('AwardType', '=', AwardType::Mastery)
    //         ->where('User', '=', $username)
    //         ->get();
    //
    //     $masteredGames = [];
    //     foreach ($awards as $award) {
    //         $masteredGames[$award->AwardData][$award->AwardDataExtra] = true;
    //     }
    //
    //     foreach ($masteredGames as $gameID => $masteryData) {
    //         if (array_key_exists($gameID, $this->gameAchievements)) {
    //             $coreAchievementCount = $this->gameAchievements[$gameID];
    //         } else {
    //             // TODO use Achievement model
    //             $coreAchievementCount = DB::table('Achievements')
    //                 ->where('GameID', '=', $gameID)
    //                 ->where('Flags', '=', AchievementFlag::OfficialCore)
    //                 ->count();
    //             $this->gameAchievements[$gameID] = $coreAchievementCount;
    //         }
    //
    //         // TODO use PlayerAchievement model
    //         $userUnlocks = DB::table('Awarded')
    //             ->select(['Awarded.HardcoreMode', DB::raw('COUNT(Awarded.AchievementID) AS Num')])
    //             ->leftJoin('Achievements', 'Achievements.ID', '=', 'Awarded.AchievementID')
    //             ->where('Achievements.GameID', '=', $gameID)
    //             ->where('Awarded.User', '=', $username)
    //             ->where('Achievements.Flags', '=', AchievementFlag::OfficialCore)
    //             ->groupBy(['Awarded.HardcoreMode'])
    //             ->pluck('Num', 'Awarded.HardcoreMode')
    //             ->toArray();
    //
    //         $hardcoreCount = $userUnlocks[UnlockMode::Hardcore] ?? 0;
    //         $softcoreCount = $userUnlocks[UnlockMode::Softcore] ?? 0;
    //
    //         $deleteAward = false;
    //         $demoteAward = false;
    //         if ($hardcoreCount === 0 && $softcoreCount === 0) {
    //             // user has no achievements for the set. if the set is empty, assume it
    //             // was demoted and keep the badge, otherwise assume they did a full reset
    //             // and destroy the badge.
    //             $deleteAward = ($coreAchievementCount !== 0);
    //         } elseif ($hardcoreCount < $coreAchievementCount) {
    //             if ($softcoreCount < $coreAchievementCount) {
    //                 // if the user has at least one unlock for the set, assume there was
    //                 // a revision and do nothing. if they want to get rid of the badge,
    //                 // they can reset one or more of the achievements they have.
    //             } elseif ($masteryData[UnlockMode::Hardcore] ?? false) {
    //                 // user has a hardcore badge, but only the softcore achievements, demote it
    //                 $demoteAward = true;
    //             }
    //         }
    //
    //         if ($deleteAward) {
    //             // user no longer has all achievements for the set, revoke their badge
    //             // TODO use PlayerBadge model
    //             DB::table('SiteAwards')
    //                 ->where('AwardType', '=', AwardType::Mastery)
    //                 ->where('User', '=', $username)
    //                 ->where('AwardData', '=', $gameID)
    //                 ->delete();
    //         } elseif ($demoteAward) {
    //             // user has all softcore achievements for the set, but no longer has
    //             // all hardcore achievements for the set
    //             if ($masteryData[UnlockMode::Softcore] ?? false) {
    //                 // user already has a separate softcore badge, delete the hardcore one
    //                 // TODO use PlayerBadge model
    //                 DB::table('SiteAwards')
    //                     ->where('AwardType', '=', AwardType::Mastery)
    //                     ->where('User', '=', $username)
    //                     ->where('AwardData', '=', $gameID)
    //                     ->where('AwardDataExtra', '=', UnlockMode::Hardcore)
    //                     ->delete();
    //             } else {
    //                 // user only has a hardcore badge, demote it to softcore
    //                 DB::connection('mysql')
    //                     ->table('SiteAwards')
    //                     ->where('AwardType', '=', AwardType::Mastery)
    //                     ->where('User', '=', $username)
    //                     ->where('AwardData', '=', $gameID)
    //                     ->update(['AwardDataExtra' => UnlockMode::Softcore]);
    //             }
    //         }
    //     }
    // }

    // private function checkCompletionBadge(User $user, Game $game, bool $hardcore): void
    // {
    //     $data = getUnlockCounts($game->id, $user->username, $hardcore);
    //     if (empty($data)) {
    //         return;
    //     }
    //
    //     $minToCompleteGame = 6;
    //
    //     if ($playerGame->game->achievements_published < 6) {
    //         $user->playerBadges()
    //             ->where('AwardType', AwardType::Mastery)
    //             ->where('AwardData', $game->id)
    //             ->delete();
    //
    //         return;
    //     }
    //
    //     $awardBadge = null;
    //     if ($hardcore && $data['NumAwardedHC'] === $data['NumAch']) {
    //         // all hardcore achievements unlocked, award mastery
    //         $awardBadge = UnlockMode::Hardcore;
    //     } elseif ($data['NumAwardedSC'] === $data['NumAch']) {
    //         if ($hardcore && $playerBadgeExists($user->username, AwardType::Mastery, $game->id, UnlockMode::Softcore)) {
    //             // when unlocking a hardcore achievement, don't update the completion
    //             // date if the user already has a completion badge
    //         } else {
    //             $awardBadge = UnlockMode::Softcore;
    //         }
    //     }
    //
    //     if ($awardBadge !== null) {
    //         if (!$playerBadgeExists($user->username, AwardType::Mastery, $game->id, $awardBadge)) {
    //             $badge = AddSiteAward(
    //                 $user->username,
    //                 AwardType::Mastery,
    //                 $game->id,
    //                 $awardBadge,
    //                 $hardcore ? $lastUnlockHardcoreAt : $lastUnlockAt,
    //             );
    //
    //             PlayerBadgeAwarded::dispatch($badge);
    //             PlayerGameCompleted::dispatch($user, $game->id);
    //
    //             if ($awardBadge === UnlockMode::Hardcore) {
    //                 static_addnewhardcoremastery($game->id, $user->username);
    //             }
    //         }
    //
    //         expireGameTopAchievers($game->id);
    //     }
    // }
    //
    // public function playerBadgeExists(string $username, int $awardType, int $data, ?int $dataExtra = null): bool
    // {
    //     $badge = PlayerBadge::where('User', $username)
    //         ->where('AwardType', $awardType)
    //         ->where('AwardData', $data);
    //
    //     if ($dataExtra !== null) {
    //         $badge->where('AwardDataExtra', $dataExtra);
    //     }
    //
    //     return $badge->exists();
    // }
}
