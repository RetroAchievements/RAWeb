<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\PlayerGameAttached;
use App\Platform\Models\Game;
use App\Site\Models\User;

class AttachPlayerGameAction
{
    public function execute(User $user, Game $game): Game
    {
        // upsert game attachment without running into unique constraints
        /** @var ?Game $gameWithPivot */
        $gameWithPivot = $user->games()->find($game);

        if ($gameWithPivot) {
            return $gameWithPivot;
        }

        $user->games()->attach($game);

        /** @var Game $gameWithPivot */
        $gameWithPivot = $user->games()->find($game);

        // let everyone know that this user started this game for first time
        PlayerGameAttached::dispatch($user, $gameWithPivot);

        return $gameWithPivot;
    }
}
