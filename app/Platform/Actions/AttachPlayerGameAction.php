<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Events\PlayerGameAttached;

class AttachPlayerGameAction
{
    public function execute(User $user, Game $game): PlayerGame
    {
        // upsert game attachment without running into unique constraints

        $playerGame = PlayerGame::firstOrCreate(
            [
                'user_id' => $user->id,
                'game_id' => $game->id,
            ],
            [
                // Default values for new records.
                'last_played_at' => null,
            ]
        );

        // Only dispatch event if this was newly created.
        if ($playerGame->wasRecentlyCreated) {
            PlayerGameAttached::dispatch($user, $game);
        }

        return $playerGame;
    }
}
