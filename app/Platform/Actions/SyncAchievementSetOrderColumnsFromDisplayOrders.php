<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;

class SyncAchievementSetOrderColumnsFromDisplayOrders
{
    public function execute(Achievement $achievement): void
    {
        // Get all of the game's achievements.
        $gameAchievements = $achievement->game->achievements;

        // For each achievement, update the order_column in the pivot table.
        foreach ($achievement->achievementSets as $achievementSet) {
            foreach ($gameAchievements as $gameAchievement) {
                $achievementSet->achievements()->updateExistingPivot(
                    $gameAchievement->id,
                    ['order_column' => $gameAchievement->DisplayOrder]
                );
            }
        }
    }
}
