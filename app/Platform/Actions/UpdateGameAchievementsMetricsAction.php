<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

class UpdateGameAchievementsMetricsAction
{
    public function execute(Game $game, bool $shouldRecalculateAchievementUnlockCounts = true): void
    {
        // TODO refactor to do this for each achievement set

        // force all unachieved to be 1
        $achievements = $game->achievements()->promoted()->get();
        if ($achievements->isEmpty()) {
            return;
        }

        $action = app(UpdateAchievementMetricsAction::class);
        if ($shouldRecalculateAchievementUnlockCounts) {
            $action->update($game, $achievements);
        } else {
            $action->updateFromStoredUnlockCounts($game, $achievements);
        }

        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }
}
