<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\PlayerGameMetricsUpdated;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UpdatePlayerGameMetrics
{
    public function execute(PlayerGame $playerGame, bool $silent = false): void
    {
        // TODO do this for each player_achievement_set as soon as achievement set separation is introduced
        // TODO store aggregates of all player_achievement_set on player_games metrics

        $game = $playerGame->game;
        $user = $playerGame->user;

        if (!$user) {
            return;
        }

        $achievementsUnlocked = $user->achievements()->where('GameID', $game->id)
            ->published()
            ->withPivot([
                'unlocker_id',
                'unlocked_at',
                'unlocked_hardcore_at',
            ])
            ->get();
        $achievementsUnlockedHardcore = $achievementsUnlocked->filter(fn (Achievement $achievement) => $achievement->pivot->unlocked_hardcore_at !== null);

        $points = $achievementsUnlocked->sum('Points');
        $pointsHardcore = $achievementsUnlockedHardcore->sum('Points');
        $pointsWeighted = $achievementsUnlockedHardcore->sum('TrueRatio');

        $playerAchievements = $achievementsUnlocked->pluck('pivot');
        $playerAchievementsHardcore = $playerAchievements->whereNotNull('unlocked_hardcore_at');
        $achievementsUnlockedCount = $playerAchievements->count();
        $achievementsUnlockedHardcoreCount = $playerAchievementsHardcore->count();

        $firstUnlockAt = $playerAchievements->min('unlocked_at');
        $lastUnlockAt = $playerAchievements->max('unlocked_at');

        $firstUnlockHardcoreAt = $playerAchievementsHardcore->min('unlocked_hardcore_at');
        $lastUnlockHardcoreAt = $playerAchievementsHardcore->max('unlocked_hardcore_at');

        // Coalesce dates to existing values or unlock dates

        /** @var ?Carbon $startedAt */
        $startedAt = $playerGame->created_at !== null
            ? min($firstUnlockAt, $playerGame->created_at)
            : $firstUnlockAt;

        $createdAt = $playerGame->created_at !== null
            ? $playerGame->created_at
            : $startedAt;

        $lastPlayedAt = $playerAchievements->whereNull('unlocker_id')->pluck('unlocked_at')
            ->merge($playerAchievementsHardcore->whereNull('unlocker_id')->pluck('unlocked_hardcore_at'))
            ->add($playerGame->last_played_at)
            ->filter()
            ->max();

        $timeTaken = $startedAt ? $startedAt->diffInSeconds($lastPlayedAt) : $playerGame->time_taken;
        $timeTakenHardcore = $startedAt ? $startedAt->diffInSeconds($lastPlayedAt) : $playerGame->time_taken_hardcore;

        $session = $user->playerSessions()
            ->where('game_id', $game->id)
            ->orderByDesc('updated_at')
            ->first();

        $playerGame->fill([
            'game_hash_id' => $session?->game_hash_id,
            'achievement_set_version_hash' => $game->achievement_set_version_hash,
            'achievements_total' => $game->achievements_published,
            'achievements_unlocked' => $achievementsUnlockedCount,
            'achievements_unlocked_hardcore' => $achievementsUnlockedHardcoreCount,
            'last_played_at' => $lastPlayedAt,
            // 'playtime_total' => $playtimeTotal,
            'time_taken' => $timeTaken,
            'time_taken_hardcore' => $timeTakenHardcore,
            'last_unlock_at' => $lastUnlockAt,
            'last_unlock_hardcore_at' => $lastUnlockHardcoreAt,
            'first_unlock_at' => $firstUnlockAt,
            'first_unlock_hardcore_at' => $firstUnlockHardcoreAt,
            'points_total' => $game->points_total,
            'points' => $points,
            'points_hardcore' => $pointsHardcore,
            'points_weighted_total' => $game->TotalTruePoints,
            'points_weighted' => $pointsWeighted,
            'created_at' => $createdAt,
        ]);

        $playerGame->fill($this->beatProgressMetrics($playerGame, $achievementsUnlocked));
        $playerGame->fill($this->completionProgressMetrics($playerGame));

        $playerGame->save();

        if (!$silent) {
            PlayerGameMetricsUpdated::dispatch($user, $game);
        }

        app()->make(RevalidateAchievementSetBadgeEligibility::class)->execute($playerGame);

        expireGameTopAchievers($game->id);
    }

    /**
     * @param Collection<int, Achievement> $achievementsUnlocked
     */
    public function beatProgressMetrics(PlayerGame $playerGame, Collection $achievementsUnlocked): array
    {
        $achievements = $playerGame->game->achievements()->published()->get();
        $totalProgressions = $achievements->where('type', AchievementType::Progression)->count();
        $totalWinConditions = $achievements->where('type', AchievementType::WinCondition)->count();

        // If the game has no beaten-tier achievements assigned, it is not considered beatable.
        // Bail.
        if (!$totalProgressions && !$totalWinConditions) {
            return [
                'achievements_beat' => null,
                'achievements_beat_unlocked' => null,
                'achievements_beat_unlocked_hardcore' => null,
                'beaten_percentage' => null,
                'beaten_percentage_hardcore' => null,
                'beaten_at' => null,
                'beaten_hardcore_at' => null,
            ];
        }

        $progressionUnlocks = $achievementsUnlocked->where('type', AchievementType::Progression)->pluck('pivot');
        $progressionUnlocksHardcore = $progressionUnlocks->filter(fn (PlayerAchievement $playerAchievement) => $playerAchievement->unlocked_hardcore_at !== null);
        $winConditionUnlocks = $achievementsUnlocked->where('type', AchievementType::WinCondition)->pluck('pivot');
        $winConditionUnlocksHardcore = $winConditionUnlocks->filter(fn (PlayerAchievement $playerAchievement) => $playerAchievement->unlocked_hardcore_at !== null);
        $progressionUnlocksSoftcoreCount = $progressionUnlocks->count();
        $progressionUnlocksHardcoreCount = $progressionUnlocksHardcore->count();
        $winConditionUnlocksSoftcoreCount = $winConditionUnlocks->count();
        $winConditionUnlocksHardcoreCount = $winConditionUnlocksHardcore->count();

        // If there are no Win Condition achievements in the set, the game is considered beaten
        // if the user unlocks all the progression achievements.
        $neededWinConditionAchievements = $totalWinConditions >= 1 ? 1 : 0;

        $isBeatenSoftcore =
            $progressionUnlocksSoftcoreCount === $totalProgressions
            && $winConditionUnlocksSoftcoreCount >= $neededWinConditionAchievements;

        $isBeatenHardcore =
            $progressionUnlocksHardcoreCount === $totalProgressions
            && $winConditionUnlocksHardcoreCount >= $neededWinConditionAchievements;

        $beatAchievements = $totalProgressions + $neededWinConditionAchievements;
        $beatAchievementsUnlockedCount = $progressionUnlocksSoftcoreCount + min($neededWinConditionAchievements, $winConditionUnlocksSoftcoreCount);
        $beatAchievementsUnlockedHardcoreCount = $progressionUnlocksHardcoreCount + min($neededWinConditionAchievements, $winConditionUnlocksHardcoreCount);

        $beatenAt = $isBeatenSoftcore ? $playerGame->beaten_at : null;
        $beatenHardcoreAt = $isBeatenHardcore ? $playerGame->beaten_hardcore_at : null;
        $beatenDates = $playerGame->beaten_dates;
        $beatenDatesHardcore = $playerGame->beaten_dates_hardcore;
        if (!$beatenAt && $isBeatenSoftcore) {
            $beatenAt = collect([
                $progressionUnlocks->max('unlocked_at'),
                $winConditionUnlocks->min('unlocked_at'),
            ])
                ->filter()
                ->max();
            $beatenDates = (new Collection($beatenDates))->push($beatenAt);
        }
        if (!$beatenHardcoreAt && $isBeatenHardcore) {
            $beatenHardcoreAt = collect([
                $progressionUnlocksHardcore->max('unlocked_hardcore_at'),
                $winConditionUnlocksHardcore->min('unlocked_hardcore_at'),
            ])
                ->filter()
                ->max();
            $beatenDatesHardcore = (new Collection($beatenDates))->push($beatenHardcoreAt);
        }

        return [
            'achievements_beat' => $beatAchievements,
            'achievements_beat_unlocked' => $beatAchievementsUnlockedCount,
            'achievements_beat_unlocked_hardcore' => $beatAchievementsUnlockedHardcoreCount,
            'beaten_percentage' => $beatAchievements ? $beatAchievementsUnlockedCount / $beatAchievements : null,
            'beaten_percentage_hardcore' => $beatAchievements ? $beatAchievementsUnlockedHardcoreCount / $beatAchievements : null,
            'beaten_dates' => $beatenDates,
            'beaten_dates_hardcore' => $beatenDatesHardcore,
            'beaten_at' => $beatenAt,
            'beaten_hardcore_at' => $beatenHardcoreAt,
        ];
    }

    public function completionProgressMetrics(PlayerGame $playerGame): array
    {
        if (!$playerGame->achievements_total) {
            return [
                'completed_at' => null,
                'completed_hardcore_at' => null,
                'completion_percentage' => null,
                'completion_percentage_hardcore' => null,
            ];
        }

        $isCompleted = $playerGame->achievements_unlocked === $playerGame->achievements_total;
        $isCompletedHardcore = $playerGame->achievements_unlocked_hardcore === $playerGame->achievements_total;

        $completedAt = $isCompleted ? $playerGame->completed_at : null;
        $completedHardcoreAt = $isCompletedHardcore ? $playerGame->completed_hardcore_at : null;
        $completionDates = $playerGame->completion_dates;
        $completionDatesHardcore = $playerGame->completion_dates_hardcore;
        if ($isCompleted && !$completedAt) {
            $completedAt = $playerGame->last_unlock_at;
            $completionDates = (new Collection($completionDates))
                ->push($completedAt);
        }
        if ($isCompletedHardcore && !$completedHardcoreAt) {
            $completedHardcoreAt = $playerGame->last_unlock_hardcore_at;
            $completionDatesHardcore = (new Collection($completionDates))
                ->push($completedHardcoreAt);
        }

        return [
            'completion_dates' => $completionDates,
            'completion_dates_hardcore' => $completionDatesHardcore,
            'completed_at' => $completedAt,
            'completed_hardcore_at' => $completedHardcoreAt,
            'completion_percentage' => $playerGame->achievements_total ? $playerGame->achievements_unlocked / $playerGame->achievements_total : null,
            'completion_percentage_hardcore' => $playerGame->achievements_total ? $playerGame->achievements_unlocked_hardcore / $playerGame->achievements_total : null,
        ];

        // TODO check progress and dispatch completion events if applicable
        // $justCompleted = false;
        // if ($justCompleted) {
        //     $completionDates = new Collection($playerGame->completion_dates);
        // }
        // if the set has been completed, post the mastery notification
        // if ($game && $response['achievementsRemaining'] == 0) {
        //     AchievementSetCompleted::dispatch($user, $game, $hardcore);
        // }
    }
}
