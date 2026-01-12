<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;

class SyncAchievementSetOrderColumnsFromDisplayOrdersAction
{
    public function execute(Achievement $achievement): void
    {
        $achievementSet = $achievement->achievementSet;
        if (!$achievementSet) {
            return;
        }

        // Get all of the game's achievements and update their order_column values in the pivot table.
        $gameAchievements = $achievement->game->achievements;
        foreach ($gameAchievements as $gameAchievement) {
            $achievementSet->achievements()->updateExistingPivot(
                $gameAchievement->id,
                ['order_column' => $gameAchievement->order_column]
            );
        }
    }
}
