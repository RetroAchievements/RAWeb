<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Platform\Enums\AchievementFlag;

class UpdateGameAchievementsMetrics
{
    public function execute(Game $game): void
    {
        // NOTE if game has a parent game it contains the parent game's players metrics
        $playersTotal = $game->players_total;
        $playersHardcore = $game->players_hardcore;

        // force all unachieved to be 1
        $playersHardcoreCalc = $playersHardcore ?: 1;
        $pointsWeightedTotal = 0;
        $achievements = $game->achievements()->published()->get();
        foreach ($achievements as $achievement) {
            $unlocksCount = $achievement->playerAchievements()
                ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_achievements.user_id')
                ->where('user.Untracked', false)
                ->count();
            $unlocksHardcoreCount = $achievement->playerAchievements()
                ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_achievements.user_id')
                ->where('user.Untracked', false)
                ->whereNotNull('unlocked_hardcore_at')
                ->count();

            // force all unachieved to be 1
            $unlocksHardcoreCalc = $unlocksHardcoreCount ?: 1;
            $weight = 0.4;
            $pointsWeighted = (int) (
                $achievement->points * (1 - $weight)
                + $achievement->points * (($playersHardcoreCalc / $unlocksHardcoreCalc) * $weight)
            );
            $pointsWeightedTotal += $pointsWeighted;

            $achievement->unlocks_total = $unlocksCount;
            $achievement->unlocks_hardcore_total = $unlocksHardcoreCount;
            $achievement->unlock_percentage = $playersTotal ? $unlocksCount / $playersTotal : 0;
            $achievement->unlock_hardcore_percentage = $playersHardcore ? $unlocksHardcoreCount / $playersHardcore : 0;
            $achievement->TrueRatio = $pointsWeighted;
            $achievement->save();
        }

        $game->TotalTruePoints = $pointsWeightedTotal;
        $game->save();

        // [multiset] double write
        // TODO: eventually achievement sets should be the only entities holding this data
        $this->updateAchievementSetsMetrics($game);
        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }

    private function updateAchievementSetsMetrics(Game $game): void
    {
        // We need to update denormalized AchievementSet values for each set that the game's
        // achievements live in. To do this, we'll first find all the relevant achievement sets
        // associated with this game's achievements. Then, we'll do some math and save the new
        // denormalized values.

        $allGameAchievementIds = $game->achievements->pluck('id');

        $targetAchievementSetIds = AchievementSetAchievement::whereIn('achievement_id', $allGameAchievementIds)
            ->distinct()
            ->get(['achievement_set_id']);

        $targetAchievementSets = AchievementSet::with('achievementSetAchievements.achievement')
            ->whereIn('id', $targetAchievementSetIds)
            ->get();

        foreach ($targetAchievementSets as $achievementSet) {
            $allSetAchievements = $achievementSet->achievementSetAchievements;

            $achievementsPublished = $allSetAchievements->filter(function ($item) {
                return $item->achievement->Flags === AchievementFlag::OfficialCore;
            });
            $achievementsUnpublished = $allSetAchievements->filter(function ($item) {
                return $item->achievement->Flags === AchievementFlag::Unofficial;
            });
            $pointsTotal = $achievementsPublished->sum(function ($item) {
                return $item->achievement->points;
            });
            $pointsWeighted = $achievementsPublished->sum(function ($item) {
                return $item->achievement->TrueRatio;
            });

            // This is currently tied to the game's core set player count. We may want to change
            // this in the future so achievement sets have their own discrete player counts.
            $achievementSet->players_total = $game->players_total;
            $achievementSet->players_hardcore = $game->players_hardcore;

            $achievementSet->achievements_published = $achievementsPublished->count();
            $achievementSet->achievements_unpublished = $achievementsUnpublished->count();
            $achievementSet->points_total = $pointsTotal;
            $achievementSet->points_weighted = $pointsWeighted;

            $achievementSet->save();
        }
    }
}
