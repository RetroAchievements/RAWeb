<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

class UpdateGameAchievementsMetricsAction
{
    public function execute(Game $game): void
    {
        // TODO refactor to do this for each achievement set

        // force all unachieved to be 1
        $achievements = $game->achievements()->published()->get();
        if ($achievements->isEmpty()) {
            return;
        }

        $action = new UpdateAchievementMetricsAction();
        $action->update($game, $achievements);

        // TODO GameAchievementSetMetricsUpdated::dispatch($game);
    }
}
