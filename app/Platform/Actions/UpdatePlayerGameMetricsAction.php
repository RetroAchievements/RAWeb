<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ActivityType;
use App\Community\Enums\AwardType;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerBadgeAwarded;
use App\Platform\Events\PlayerBadgeLost;
use App\Platform\Events\PlayerGameBeaten;
use App\Platform\Events\PlayerGameCompleted;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerGame;
use App\Site\Models\User;
use Illuminate\Support\Carbon;

class UpdatePlayerGameMetricsAction
{
    public function execute(PlayerGame $playerGame, bool $hardcore = false): void
    {
        // TODO do this for each player_achievement_set as soon as achievement set separation is introduced
        // TODO store aggregates of all player_achievement_set on player_games metrics

        /** @var Game $game */
        $game = $playerGame->game;

        /** @var ?User $user */
        $user = $playerGame->user;

        if (!$user) {
            return;
        }

        $unlockedAchievements = $user->achievements()->where('GameID', $game->id)
            ->published()
            ->withPivot([
                'unlocked_at',
                'unlocked_hardcore_at',
            ])
            ->get();

        $unlockedAchievementsHardcore = $unlockedAchievements->filter(fn (Achievement $achievement) => $achievement->pivot->unlocked_hardcore_at !== null);

        $playerAchievements = $unlockedAchievements->pluck('pivot');
        $playerAchievementsHardcore = $playerAchievements->whereNotNull('unlocked_hardcore_at');

        // TODO use pre-aggregated values instead of fetching models
        $achievements = $game->achievements()->published()->get();
        // TODO $game->achievements_published
        $achievementsTotal = $achievements->count();
        // TODO $game->points_total
        $pointsTotal = $achievements->sum('Points');
        // TODO $game->TotalTruePoints
        $pointsWeightedTotal = $achievements->sum('TrueRatio');

        $points = $unlockedAchievements->sum('Points');
        $pointsHardcore = $unlockedAchievementsHardcore->sum('Points');
        $pointsWeighted = $unlockedAchievements->sum('TrueRatio');

        $achievementsUnlockedCount = $playerAchievements->count();
        $achievementsUnlockedHardcoreCount = $playerAchievementsHardcore->count();

        $firstUnlockAt = $playerAchievements->min('unlocked_at');
        $lastUnlockAt = $playerAchievements->max('unlocked_at');

        $firstUnlockHardcoreAt = $playerAchievements->min('unlocked_hardcore_at');
        $lastUnlockHardcoreAt = $playerAchievements->max('unlocked_hardcore_at');

        // TODO check progress and dispatch completion events if applicable
        // $justCompleted = false;
        // if ($justCompleted) {
        //     $completionDates = new Collection($playerGame->completion_dates);
        // }
        // if the set has been completed, post the mastery notification
        // if ($game && $response['achievementsRemaining'] == 0) {
        //     AchievementSetCompleted::dispatch($user, $game, $hardcore);
        // }
        // TODO refactor to actions
        $this->checkCompletionBadge($user, $game, $hardcore);
        $beatProgress = testBeatenGame($game->id, $user->User);
        $beatAchievements = null;
        $beatAchievementsUnlockedCount = null;
        $beatAchievementsUnlockedHardcoreCount = null;
        // TODO
        $beatenAt = $playerGame->beaten_at;
        $beatenHardcoreAt = $playerGame->beaten_hardcore_at;
        $beatenDates = $playerGame->beaten_dates;
        $beatenDatesHardcore = $playerGame->beaten_dates_hardcore;
        if ($beatProgress['isBeatable']) {
            $beatAchievements = $beatProgress['achievementsTotal'];
            $beatAchievementsUnlockedCount = $beatProgress['achievementsUnlocked'];
            $beatAchievementsUnlockedHardcoreCount = $beatProgress['achievementsUnlockedHardcore'];
            $this->checkBeatenBadge($user, $game, $beatProgress);
        }

        // TODO
        $completedAt = $playerGame->completed_at;
        $completedHardcoreAt = $playerGame->completed_hardcore_at;
        $completionDates = $playerGame->completion_dates;
        $completionDatesHardcore = $playerGame->completion_dates_hardcore;

        // Coalesce dates to existing values or unlock dates

        /** @var ?Carbon $startedAt */
        $startedAt = $playerGame->created_at !== null
            ? min($firstUnlockAt, $playerGame->created_at)
            : $firstUnlockAt;

        $createdAt = $playerGame->created_at !== null
            ? $playerGame->created_at
            : $startedAt;

        $lastPlayedAt = $playerAchievements->pluck('unlocked_at')
            ->merge($playerAchievementsHardcore->pluck('unlocked_hardcore_at'))
            ->add($playerGame->last_played_at)
            ->filter()
            ->max();

        $timeTaken = $startedAt ? $startedAt->diffInSeconds($completedAt ?? $lastPlayedAt) : $playerGame->time_taken;
        $timeTakenHardcore = $startedAt ? $startedAt->diffInSeconds($completedHardcoreAt ?? $lastPlayedAt) : $playerGame->time_taken_hardcore;

        $playerGame->update([
            'update_status' => null, // reset previously added update reason
            'achievement_set_version_hash' => $game->achievement_set_version_hash,
            'achievements_total' => $achievementsTotal,
            'achievements_unlocked' => $achievementsUnlockedCount,
            'achievements_unlocked_hardcore' => $achievementsUnlockedHardcoreCount,
            'achievements_beat' => $beatAchievements,
            'beaten_percentage' => $beatAchievements ? $beatAchievementsUnlockedCount / $beatAchievements : null,
            'beaten_percentage_hardcore' => $beatAchievements ? $beatAchievementsUnlockedHardcoreCount / $beatAchievements : null,
            'completion_percentage' => $achievementsTotal ? $achievementsUnlockedCount / $achievementsTotal : null,
            'completion_percentage_hardcore' => $achievementsTotal ? $achievementsUnlockedHardcoreCount / $achievementsTotal : null,
            'last_played_at' => $lastPlayedAt,
            // 'playtime_total' => $playtimeTotal,
            'time_taken' => $timeTaken,
            'time_taken_hardcore' => $timeTakenHardcore,
            'beaten_dates' => $beatenDates,
            'beaten_dates_hardcore' => $beatenDatesHardcore,
            'completion_dates' => $completionDates,
            'completion_dates_hardcore' => $completionDatesHardcore,
            'beaten_at' => $beatenAt,
            'beaten_hardcore_at' => $beatenHardcoreAt,
            'completed_at' => $completedAt,
            'completed_hardcore_at' => $completedHardcoreAt,
            'last_unlock_at' => $lastUnlockAt,
            'last_unlock_hardcore_at' => $lastUnlockHardcoreAt,
            'first_unlock_at' => $firstUnlockAt,
            'first_unlock_hardcore_at' => $firstUnlockHardcoreAt,
            'points_total' => $pointsTotal,
            'points' => $points,
            'points_hardcore' => $pointsHardcore,
            'points_weighted_total' => $pointsWeightedTotal,
            'points_weighted' => $pointsWeighted,
            'created_at' => $createdAt,
        ]);

        PlayerGameMetricsUpdated::dispatch($user, $game);
    }

    private function checkCompletionBadge(User $user, Game $game, bool $hardcore): void
    {
        $data = getUnlockCounts($game->id, $user->username);

        $minToCompleteGame = 6;
        if ($data['NumAch'] >= $minToCompleteGame) {
            $awardBadge = null;
            if ($hardcore && $data['NumAwardedHC'] === $data['NumAch']) {
                // all hardcore achievements unlocked, award mastery
                $awardBadge = UnlockMode::Hardcore;
            } elseif ($data['NumAwardedSC'] === $data['NumAch']) {
                if ($hardcore && $this->playerBadgeExists($user->username, AwardType::Mastery, $game->id, UnlockMode::Softcore)) {
                    // when unlocking a hardcore achievement, don't update the completion
                    // date if the user already has a completion badge
                } else {
                    $awardBadge = UnlockMode::Softcore;
                }
            }

            if ($awardBadge !== null) {
                if (!$this->playerBadgeExists($user->username, AwardType::Mastery, $game->id, $awardBadge)) {
                    $badge = AddSiteAward($user->username, AwardType::Mastery, $game->id, $awardBadge);

                    PlayerBadgeAwarded::dispatch($badge);
                    PlayerGameCompleted::dispatch($user, $game->id);

                    if ($awardBadge === UnlockMode::Hardcore) {
                        static_addnewhardcoremastery($game->id, $user->username);
                    }
                }

                if (!RecentlyPostedProgressionActivity($user->username, $game->id, $awardBadge, ActivityType::CompleteGame)) {
                    postActivity($user->username, ActivityType::CompleteGame, $game->id, $awardBadge);
                }

                expireGameTopAchievers($game->id);
            }
        } else {
            $query = $user->playerBadges()
                ->where('AwardType', AwardType::Mastery)
                ->where('AwardData', $game->id);

            if ($query->exists()) {
                // TODO
                $user->playerBadges()
                    ->where('AwardType', AwardType::Mastery)
                    ->where('AwardData', $game->id)
                    ->delete();
            }
        }
    }

    private function checkBeatenBadge(User $user, Game $game, array $beatProgress): void
    {
        $playerAchievements = $beatProgress['playerAchievements'];
        $isBeatenSoftcore = $beatProgress['isBeatenSoftcore'];
        $isBeatenHardcore = $beatProgress['isBeatenHardcore'];

        $badge = PlayerBadge::where('User', $user->username)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->id);
        $softcoreBadge = $badge->where('AwardDataExtra', UnlockMode::Softcore);
        $hardcoreBadge = $badge->where('AwardDataExtra', UnlockMode::Hardcore);

        // Revoke pre-existing awards that no longer satisfy the game's "beaten" criteria.
        // If the platform changes the definition of beating a game and the user no
        // longer satisfies the criteria, they should not have the award anymore.
        if (!$isBeatenSoftcore && $softcoreBadge->exists()) {
            PlayerBadgeLost::dispatch($softcoreBadge->first());
            $softcoreBadge->delete();
        }
        if (!$isBeatenHardcore && $hardcoreBadge->exists()) {
            PlayerBadgeLost::dispatch($hardcoreBadge->first());
            $badge->where('AwardDataExtra', UnlockMode::Hardcore)->delete();
        }

        // award a badge
        $unlockMode = $isBeatenHardcore ? UnlockMode::Hardcore : UnlockMode::Softcore;
        if (!$this->playerBadgeExists($user->username, AwardType::GameBeaten, $game->id, $unlockMode)) {
            $awardDate = Carbon::parse($this->calculateBeatenGameTimestamp($playerAchievements));

            $badge = AddSiteAward(
                $user->username,
                AwardType::GameBeaten,
                $game->id,
                $unlockMode,
                $awardDate,
                displayOrder: 0
            );

            PlayerBadgeAwarded::dispatch($badge);
            PlayerGameBeaten::dispatch($user, $game->id, $isBeatenHardcore);

            if ($isBeatenHardcore && $awardDate->gte(Carbon::now()->subMinutes(10))) {
                static_addnewhardcoregamebeaten($game->id, $user->username);
            }
        }
    }

    public function playerBadgeExists(string $username, int $awardType, int $data, ?int $dataExtra = null): bool
    {
         $badge = PlayerBadge::where('User', $username)
            ->where('AwardType', $awardType)
            ->where('AwardData', $data);

        if ($dataExtra !== null) {
            $badge->where('AwardDataExtra', $dataExtra);
        }

        return $badge->exists();
    }

    /**
     * Beaten game awards are stored with an AwardDate that corresponds to when they
     * unlocked the precise achievement that granted them the beaten status. This has
     * to be calculated by on the rules that Progression and Win Condition achievements follow.
     */
    public function calculateBeatenGameTimestamp(mixed $userAchievements): string
    {
        $progressionAchievementsUnlocked = 0;
        $latestProgressionDate = null;
        $earliestWinConditionDate = null;

        foreach ($userAchievements as $achievement) {
            if ($achievement->type === AchievementType::Progression && $achievement->AchievementID) {
                $progressionAchievementsUnlocked++;
                // Keep track of the latest progression achievement date.
                $latestProgressionDate = $latestProgressionDate === null || $achievement->Date > $latestProgressionDate
                    ? $achievement->Date
                    : $latestProgressionDate;
            } elseif ($achievement->type === AchievementType::WinCondition && $achievement->AchievementID) {
                // Keep track of the earliest win condition date.
                $earliestWinConditionDate = $earliestWinConditionDate === null || $achievement->Date < $earliestWinConditionDate
                    ? $achievement->Date
                    : $earliestWinConditionDate;
            }
        }

        // Return the latest date between the progression and win condition achievements.
        return $progressionAchievementsUnlocked > 0
            ? ($latestProgressionDate ? max($latestProgressionDate, $earliestWinConditionDate) : $earliestWinConditionDate)
            : $earliestWinConditionDate;
    }
}
