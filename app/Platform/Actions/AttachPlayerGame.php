<?php

namespace App\Platform\Actions;

use App\Platform\Events\PlayerGameAttached;
use App\Platform\Models\Game;
use App\Site\Models\User;

class AttachPlayerGame
{
    public function execute(User $user, Game $game): Game
    {
        // upsert game attachment without running into unique constraints
        /** @var ?Game $playerGame */
        $playerGame = $user->games()->find($game);

        if ($playerGame) {
            return $playerGame;
        }

        $user->games()->attach($game);
        /** @var Game $playerGame */
        $playerGame = $user->games()->find($game);
        // let everyone know that this user started this game for first time
        PlayerGameAttached::dispatch($user, $playerGame);

        return $playerGame;
    }
}
