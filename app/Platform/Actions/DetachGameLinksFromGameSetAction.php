<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAlternative;
use App\Models\GameSet;

class DetachGameLinksFromGameSetAction
{
    public function execute(GameSet $gameSet, array $parentGameSetIds): void
    {
        // TODO after dropping GameAlternatives, delete this block
        // We need the game IDs of the game set links we're detaching.
        // Otherwise, we can't double-write back to the legacy GameAlternatives.
        $parentGameIds = GameSet::whereIn('id', $parentGameSetIds)
            ->pluck('game_id')
            ->toArray();
        GameAlternative::where('gameID', $gameSet->game_id)
            ->whereIn('gameIDAlt', $parentGameIds)
            ->delete();
        GameAlternative::where('gameIDAlt', $gameSet->game_id)
            ->whereIn('gameID', $parentGameIds)
            ->delete();
        // ENDTODO

        $gameSet->parents()->detach($parentGameSetIds);
        $gameSet->children()->detach($parentGameSetIds);
    }
}
