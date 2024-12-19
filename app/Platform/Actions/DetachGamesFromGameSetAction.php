<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAlternative;
use App\Models\GameSet;

// TODO after dropping GameAlternatives, this action can be deleted.
// Just use the native Filament detach action.

class DetachGamesFromGameSetAction
{
    public function execute(GameSet $gameSet, array $gameIds): void
    {
        GameAlternative::where('gameID', $gameSet->game_id)
            ->whereIn('gameIDAlt', $gameIds)
            ->delete();
        GameAlternative::where('gameIDAlt', $gameSet->game_id)
            ->whereIn('gameID', $gameIds)
            ->delete();

        $gameSet->games()->detach($gameIds);
    }
}
