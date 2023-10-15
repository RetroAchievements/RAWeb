<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\PlayerGameAttached;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Site\Models\User;
use Exception;

class AttachPlayerGame
{
    public function execute(User $user, Game $game): PlayerGame
    {
        // upsert game attachment without running into unique constraints

        $playerGame = $user->playerGames()->firstWhere('game_id', $game->id);
        if ($playerGame) {
            return $playerGame;
        }

        try {
            $user->games()->attach($game);

            // let everyone know that this user started this game for first time
            PlayerGameAttached::dispatch($user, $game);
        } catch (Exception) {
            // prevent race conditions where the game might've been attached by another job
        }

        return $user->playerGames()->firstWhere('game_id', $game->id);
    }
}
