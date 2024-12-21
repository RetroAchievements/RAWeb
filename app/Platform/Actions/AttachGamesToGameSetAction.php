<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAlternative;
use App\Models\GameSet;

// TODO after dropping GameAlternatives, this action can be deleted.
// Just use the native Filament attach action.

class AttachGamesToGameSetAction
{
    public function execute(GameSet $gameSet, array $gameIds): void
    {
        foreach ($gameIds as $gameId) {
            GameAlternative::create([
                'gameID' => $gameSet->game_id,
                'gameIDAlt' => $gameId,
            ]);
            GameAlternative::create([
                'gameID' => $gameId,
                'gameIDAlt' => $gameSet->game_id,
            ]);
        }

        $gameSet->games()->attach($gameIds);
    }
}
