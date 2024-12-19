<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAlternative;
use App\Models\GameSet;

class AttachGameLinksToGameSetAction
{
    public function execute(GameSet $gameSet, array $parentGameSetIds): void
    {
        // TODO after dropping GameAlternatives, delete this block
        // We need the game IDs of the game set links we're attaching.
        // Otherwise, we can't double-write back to the legacy GameAlternatives.
        $parentGameIds = GameSet::whereIn('id', $parentGameSetIds)
            ->pluck('game_id')
            ->toArray();
        foreach ($parentGameIds as $gameId) {
            GameAlternative::create([
                'gameID' => $gameSet->game_id,
                'gameIDAlt' => $gameId,
            ]);
            GameAlternative::create([
                'gameID' => $gameId,
                'gameIDAlt' => $gameSet->game_id,
            ]);
        }
        // ENDTODO

        $gameSet->parents()->attach($parentGameSetIds);
        $gameSet->children()->attach($parentGameSetIds);
    }
}
