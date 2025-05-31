<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

// Recalculates the number of players for a game.
class UpdateGamePlayerCountAction
{
    public function execute(Game $game): void
    {
        $parentGame = $game->parentGame();
        if ($parentGame) {
            // NOTE: This assumes everyone who plays a child set also plays the parent set.
            //       These counts should technically be the union of users from both sets.
            if ($parentGame->players_total > 0) {
                $game->players_total = $parentGame->players_total;
                $game->players_hardcore = $parentGame->players_hardcore;
            } else {
                $parentGame = null;
            }
        }

        if (!$parentGame) {
            $game->players_total = $game->playerGames()
                ->where('achievements_unlocked', '>', 0)
                ->whereHas('user', function ($query) { $query->tracked(); })
                ->count();
            $game->players_hardcore = $game->playerGames()
                ->where('achievements_unlocked_hardcore', '>', 0)
                ->whereHas('user', function ($query) { $query->tracked(); })
                ->count();
        }

        if ($game->isDirty()) {
            $game->saveQuietly();

            // if the player count changed, update unlock percentages and weighted points for all achievements in the set
            app()->make(UpdateGameAchievementsMetricsAction::class)
                ->execute($game);
        }
    }
}
