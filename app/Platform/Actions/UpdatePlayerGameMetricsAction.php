<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievement;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Services\PlayerGameActivityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UpdatePlayerGameMetricsAction
{
    public function execute(PlayerGame $playerGame, bool $silent = false): void
    {
        $game = $playerGame->game;
        $user = $playerGame->user;

        if (!$user) {
            return;
        }

        $activityService = new PlayerGameActivityService();
        $activityService->initialize($playerGame->user, $playerGame->game, withSubsets: true);

        $summary = $activityService->summarize();
        $playerGame->last_played_at = $activityService->lastPlayedAt();
        $playerGame->playtime_total = $summary['totalPlaytime'];

        $gameAchievementSets = GameAchievementSet::where('game_id', $game->id)
            ->with(['achievementSet.achievements' => 
                fn ($q) => $q->where('Flags', AchievementFlag::OfficialCore)
                             ->select(['Achievements.ID', 'type', 'Points', 'TrueRatio'])
            ])
            ->get();

        // find all achievements for all sets
        $achievementIds = [];
        $coreAchievementSet = null;
        foreach ($gameAchievementSets as $gameAchievementSet) {
            if ($gameAchievementSet->type === AchievementSetType::Core) {
                $coreAchievementSet = $gameAchievementSet->achievementSet;
            }

            foreach ($gameAchievementSet->achievementSet->achievements as $achievement) {
                $achievementIds[] = $achievement->id;
            }
        }
        $achievementIds = array_unique($achievementIds);

        // get unlocks for all found achievements
        $achievementsUnlocked = $user->achievements()->whereIn('Achievements.ID', $achievementIds)
            ->withPivot([
                'unlocked_at',
                'unlocked_hardcore_at',
            ])
            ->select(['Achievements.ID', 'Points', 'TrueRatio'])
            ->get();
        $achievementsUnlockedHardcore = $achievementsUnlocked->filter(fn (Achievement $achievement) => $achievement->pivot->unlocked_hardcore_at !== null);

        // ==========================
        // legacy support until everything is migrated - core+subset metrics go into the all_ fields
        $playerGame->all_achievements_total = count($achievementIds);
        $playerGame->all_achievements_unlocked = $achievementsUnlocked->count();
        $playerGame->all_achievements_unlocked_hardcore = $achievementsUnlockedHardcore->count();
        $playerGame->all_points_total = Achievement::whereIn('ID', $achievementIds)->sum('Points');
        $playerGame->all_points = $achievementsUnlocked->sum('Points');
        $playerGame->all_points_hardcore = $achievementsUnlockedHardcore->sum('Points');
        $playerGame->all_points_weighted = $achievementsUnlockedHardcore->sum('TrueRatio');
        // ==========================

        // process each set
        $playerAchievementSets = [];
        foreach ($gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;
            $playerAchievementSet = PlayerAchievementSet::where('user_id', $playerGame->user->id)
                ->where('achievement_set_id', $achievementSet->id)
                ->first();

            if (!$playerAchievementSet) {
                $playerAchievementSet = new PlayerAchievementSet([
                    'user_id' => $playerGame->user->id,
                    'achievement_set_id' => $achievementSet->id,
                    'created_at' => $playerGame->created_at,
                ]);
            }

            $setAchievementIds = [];
            foreach ($achievementSet->achievements as $achievement) {
                $setAchievementIds[] = $achievement->id;
            }

            $setAchievementsUnlocked = $achievementsUnlocked->filter(fn (Achievement $achievement) => in_array($achievement->id, $setAchievementIds));
            if ($setAchievementsUnlocked->count() === 0 && $achievementSet !== $coreAchievementSet && !$playerAchievementSet->exists) {
                // no unlocks for the subset and no existing metrics, don't create a player_achievement_set
                continue;
            }
            $setAchievementsUnlockedHardcore = $achievementsUnlockedHardcore->filter(fn (Achievement $achievement) => in_array($achievement->id, $setAchievementIds));

            $playerAchievementSet->achievements_unlocked = $setAchievementsUnlocked->count();
            $playerAchievementSet->achievements_unlocked_hardcore = $setAchievementsUnlockedHardcore->count();
            $playerAchievementSet->points = $setAchievementsUnlocked->sum('Points');
            $playerAchievementSet->points_hardcore = $setAchievementsUnlockedHardcore->sum('Points');
            $playerAchievementSet->points_weighted = $setAchievementsUnlockedHardcore->sum('TrueRatio');

            $summary = $activityService->getAchievementSetMetrics($playerAchievementSet);
            $playerAchievementSet->time_taken = $summary['achievementPlaytimeSoftcore'];
            $playerAchievementSet->time_taken_hardcore = $summary['achievementPlaytimeHardcore'];
            $playerAchievementSet->last_unlock_at = $summary['lastUnlockTimeSoftcore'];
            $playerAchievementSet->last_unlock_hardcore_at = $summary['lastUnlockTimeHardcore'];

            // update completion progress
            $numSetAchievements = count($setAchievementIds);
            if ($numSetAchievements > 0) {
                $this->updateCompletionMetrics($playerAchievementSet, $playerGame, $numSetAchievements, $achievementSet === $coreAchievementSet);
            } else {
                $playerAchievementSet->completion_percentage = 0.0;
                $playerAchievementSet->completion_percentage_hardcore = 0.0;
            }

            $playerAchievementSet->save();

            // ==========================
            // legacy support until everything is migrated - copy core metrics to the player_game base fields
            if ($achievementSet === $coreAchievementSet) {
                $playerGame->achievements_total = count($setAchievementIds);
                $playerGame->achievements_unlocked = $setAchievementsUnlocked->count();
                $playerGame->achievements_unlocked_hardcore = $setAchievementsUnlockedHardcore->count();
                $playerGame->achievements_unlocked_softcore = $playerGame->achievements_unlocked - $playerGame->achievements_unlocked_hardcore;
                $playerGame->points_total = $achievementSet->achievements->sum('Points');
                $playerGame->points = $setAchievementsUnlocked->sum('Points');
                $playerGame->points_hardcore = $setAchievementsUnlockedHardcore->sum('Points');
                $playerGame->points_weighted = $setAchievementsUnlockedHardcore->sum('TrueRatio');
                $playerGame->completion_percentage = $playerAchievementSet->completion_percentage;
                $playerGame->completion_percentage_hardcore = $playerAchievementSet->completion_percentage_hardcore;
            }
            // ==========================
        }

        $playerAchievements = $achievementsUnlocked->pluck('pivot');
        $playerGame->first_unlock_at = $playerAchievements->min('unlocked_at');
        $playerGame->last_unlock_at = $playerAchievements->max('unlocked_at');
        $playerGame->last_unlock_hardcore_at = $playerAchievements->max('unlocked_hardcore_at');

        $playerGame->fill($this->beatProgressMetrics($playerGame, $coreAchievementSet, $achievementsUnlocked));

        $beatSummary = $activityService->getBeatProgressMetrics($coreAchievementSet, $playerGame);
        $playerGame->time_to_beat = $beatSummary['beatPlaytimeSoftcore'];
        $playerGame->time_to_beat_hardcore = $beatSummary['beatPlaytimeHardcore'];

        $playerGame->save();

        if (!$silent) {
            PlayerGameMetricsUpdated::dispatch($user, $game);
        }

        app()->make(RevalidateAchievementSetBadgeEligibilityAction::class)->execute($playerGame);

        expireGameTopAchievers($game->id);
    }

    private function updateCompletionMetrics(PlayerAchievementSet $playerAchievementSet, PlayerGame $playerGame, int $numSetAchievements, bool $isCoreSet): void
    {
        $completionDates = $playerAchievementSet->completion_dates ?? [];
        $completionDatesHardcore = $playerAchievementSet->completion_dates_hardcore ?? [];
        $gameCompletionDates = $playerGame->completion_dates ?? [];
        $gameCompletionDatesHardcore = $playerGame->completion_dates_hardcore ?? [];

        $playerAchievementSet->completion_percentage = $playerAchievementSet->achievements_unlocked / $numSetAchievements;
        $isCompleted = $playerAchievementSet->achievements_unlocked === $numSetAchievements;        
        if ($isCompleted && !$playerAchievementSet->completed_at) {
            $playerAchievementSet->completed_at = $playerAchievementSet->last_unlock_at;
            array_push($completionDates, $playerAchievementSet->completed_at);

            if ($isCoreSet) {
                $playerGame->completed_at = $playerAchievementSet->last_unlock_at;
                array_push($gameCompletionDates, $playerGame->completed_at);
            }
        }
        $playerAchievementSet->completion_dates = empty($completionDates) ? null : array_unique($completionDates);

        $playerAchievementSet->completion_percentage_hardcore = $playerAchievementSet->achievements_unlocked_hardcore / $numSetAchievements;
        $isCompletedHardcore = $playerAchievementSet->achievements_unlocked_hardcore === $numSetAchievements;
        if ($isCompletedHardcore && !$playerAchievementSet->completed_hardcore_at) {
            $playerAchievementSet->completed_hardcore_at = $playerAchievementSet->last_unlock_hardcore_at;
            array_push($completionDatesHardcore, $playerAchievementSet->completed_hardcore_at);

            if ($isCoreSet) {
                $playerGame->completed_hardcore_at = $playerAchievementSet->last_unlock_hardcore_at;
                if ($playerGame->completion_dates_hardcore === null) {
                    $playerGame->completion_dates_hardcore = [];
                }
                array_push($gameCompletionDatesHardcore, $playerGame->completed_hardcore_at);
            }
        }
        $playerAchievementSet->completion_dates_hardcore = empty($completionDatesHardcore) ? null : array_unique($completionDatesHardcore);

        if ($isCoreSet) {
            $playerAchievementSet->completion_dates = empty($gameCompletionDates) ? null : array_unique($gameCompletionDates);
            $playerAchievementSet->completion_dates_hardcore = empty($gameCompletionDatesHardcore) ? null : array_unique($gameCompletionDatesHardcore);
        }
    }

    /**
     * @param Collection<int, Achievement> $achievementsUnlocked
     */
    public function beatProgressMetrics(PlayerGame $playerGame, AchievementSet $coreAchievementSet, Collection $achievementsUnlocked): array
    {
        $progressionAchievementIds = $coreAchievementSet->achievements->where('type', AchievementType::Progression)->pluck('ID')->toArray();
        $winConditionIds = $coreAchievementSet->achievements->where('type', AchievementType::WinCondition)->pluck('ID')->toArray();

        // If the game has no beaten-tier achievements assigned, it is not considered beatable.
        // Bail.
        if (empty($progressionAchievementIds) && empty($winConditionIds)) {
            return [
                'beaten_at' => null,
                'beaten_hardcore_at' => null,
            ];
        }

        $progressionUnlocks = $achievementsUnlocked->whereIn('ID', $progressionAchievementIds)->pluck('pivot');
        $progressionUnlocksHardcore = $progressionUnlocks->filter(fn (PlayerAchievement $playerAchievement) => $playerAchievement->unlocked_hardcore_at !== null);
        $winConditionUnlocks = $achievementsUnlocked->whereIn('ID', $winConditionIds)->pluck('pivot');
        $winConditionUnlocksHardcore = $winConditionUnlocks->filter(fn (PlayerAchievement $playerAchievement) => $playerAchievement->unlocked_hardcore_at !== null);
        $progressionUnlocksSoftcoreCount = $progressionUnlocks->count();
        $progressionUnlocksHardcoreCount = $progressionUnlocksHardcore->count();
        $winConditionUnlocksSoftcoreCount = $winConditionUnlocks->count();
        $winConditionUnlocksHardcoreCount = $winConditionUnlocksHardcore->count();

        // If there are no Win Condition achievements in the set, the game is considered beaten
        // if the user unlocks all the progression achievements.
        $neededWinConditionAchievements = !empty($winConditionIds) ? 1 : 0;
        $totalProgressions = count($progressionAchievementIds);

        $isBeatenSoftcore =
            $progressionUnlocksSoftcoreCount === $totalProgressions
            && $winConditionUnlocksSoftcoreCount >= $neededWinConditionAchievements;

        $isBeatenHardcore =
            $progressionUnlocksHardcoreCount === $totalProgressions
            && $winConditionUnlocksHardcoreCount >= $neededWinConditionAchievements;

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
            array_push($beatenDates, $beatenAt);
        }
        if ($beatenDates !== null) {
            $beatenDates = array_unique($beatenDates);
        }

        if (!$beatenHardcoreAt && $isBeatenHardcore) {
            $beatenHardcoreAt = collect([
                $progressionUnlocksHardcore->max('unlocked_hardcore_at'),
                $winConditionUnlocksHardcore->min('unlocked_hardcore_at'),
            ])
                ->filter()
                ->max();
            array_push($beatenDatesHardcore, $beatenHardcoreAt);
        }
        if ($beatenDatesHardcore !== null) {
            $beatenDatesHardcore = array_unique($beatenDatesHardcore);
        }

        return [
            'beaten_dates' => $beatenDates,
            'beaten_dates_hardcore' => $beatenDatesHardcore,
            'beaten_at' => $beatenAt,
            'beaten_hardcore_at' => $beatenHardcoreAt,
        ];
    }
}
