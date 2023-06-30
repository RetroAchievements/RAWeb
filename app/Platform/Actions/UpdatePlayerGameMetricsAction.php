<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Site\Models\User;
use Carbon\Carbon;

class UpdatePlayerGameMetricsAction
{
    public function execute(PlayerGame $playerGame): void
    {
        // TODO this should be done for each player_achievement_set, too, as soon as achievement set separation is introduced
        // TODO store aggregates of all player_achievement_set on player_games metrics

        /** @var Game $game */
        $game = $playerGame->game;

        /** @var User $game */
        $user = $playerGame->user;

        $unlockedAchievements = $user->achievements()->where('GameID', $game->ID)
            ->withPivot([
                'unlocked_at',
                'unlocked_hardcore_at',
            ])
            ->get();

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
        $pointsWeighted = $unlockedAchievements->sum('TrueRatio');

        $achievementsUnlockedCount = $playerAchievements->count();
        $achievementsUnlockedHardcoreCount = $playerAchievementsHardcore->count();

        $firstUnlockAt = $playerAchievements->min('unlocked_at');
        $lastUnlockAt = $playerAchievements->max('unlocked_at');

        $firstUnlockHardcoreAt = $playerAchievements->min('unlocked_hardcore_at');
        $lastUnlockHardcoreAt = $playerAchievements->max('unlocked_hardcore_at');

        // TODO check completion state here and dispatch completion events if applicable

        $completedAt = $playerGame->completed_at;
        $completedHardcoreAt = $playerGame->completed_hardcore_at;
        $completionDates = $playerGame->completion_dates;
        $completionDatesHardcore = $playerGame->completion_dates_hardcore;
        // $justCompleted = false;
        // if ($justCompleted) {
        //     $completionDates = new Collection($playerGame->completion_dates);
        // }

        // Coalesce dates to existing values or unlock dates

        /** @var Carbon $startedAt */
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

        $timeTaken = $startedAt->diffInSeconds($completedAt ?? $lastPlayedAt);
        $timeTakenHardcore = $startedAt->diffInSeconds($completedHardcoreAt ?? $lastPlayedAt);

        $metrics = [
            'achievements_total' => $achievementsTotal,
            'achievements_unlocked' => $achievementsUnlockedCount,
            'achievements_unlocked_hardcore' => $achievementsUnlockedHardcoreCount,
            'completion_percentage' => $achievementsUnlockedCount / $achievementsTotal * 100,
            'completion_percentage_hardcore' => $achievementsUnlockedHardcoreCount / $achievementsTotal * 100,
            'last_played_at' => $lastPlayedAt,
            // 'playtime_total' => $playtimeTotal,
            'time_taken' => $timeTaken,
            'time_taken_hardcore' => $timeTakenHardcore,
            'completion_dates' => $completionDates,
            'completion_dates_hardcore' => $completionDatesHardcore,
            'completed_at' => $completedAt,
            'completed_hardcore_at' => $completedHardcoreAt,
            'last_unlock_at' => $lastUnlockAt,
            'last_unlock_hardcore_at' => $lastUnlockHardcoreAt,
            'first_unlock_at' => $firstUnlockAt,
            'first_unlock_hardcore_at' => $firstUnlockHardcoreAt,
            'points_total' => $pointsTotal,
            'points' => $points,
            'points_weighted_total' => $pointsWeightedTotal,
            'points_weighted' => $pointsWeighted,
            'created_at' => $createdAt,
        ];

        $playerGame->update($metrics);
    }
}
