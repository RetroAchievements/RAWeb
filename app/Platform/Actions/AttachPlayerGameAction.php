<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Events\PlayerGameAttached;
use Exception;

class AttachPlayerGameAction
{
    public function execute(User $user, Game $game): PlayerGame
    {
        // upsert game attachment without running into unique constraints

        $playerGame = $user->playerGame($game);
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

        return $user->playerGame($game);
    }
}
