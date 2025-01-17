<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameSet;
use App\Platform\Enums\GameSetType;

class LinkSimilarGamesAction
{
    public function execute(Game $parentGame, array $gameIdsToLink): void
    {
        // Add bidirectional alternative game links.
        foreach ($gameIdsToLink as $gameId) {
            GameAlternative::create([
                'gameID' => $parentGame->id,
                'gameIDAlt' => $gameId,
            ]);
            GameAlternative::create([
                'gameID' => $gameId,
                'gameIDAlt' => $parentGame->id,
            ]);
        }

        $parentSimilarGamesSet = GameSet::firstOrCreate([
            'type' => GameSetType::SimilarGames,
            'game_id' => $parentGame->id,
        ]);
        $parentSimilarGamesSet->games()->attach($gameIdsToLink);

        // Link each game's similar games set to include the parent game.
        foreach ($gameIdsToLink as $gameId) {
            $similarGamesSet = GameSet::firstOrCreate([
                'type' => GameSetType::SimilarGames,
                'game_id' => $gameId,
            ]);
            $similarGamesSet->games()->attach($parentGame->id);
        }
    }
}
