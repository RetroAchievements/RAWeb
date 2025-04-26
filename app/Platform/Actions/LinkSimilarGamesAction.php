<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Platform\Enums\GameSetType;

class LinkSimilarGamesAction
{
    public function execute(Game $parentGame, array $gameIdsToLink): void
    {
        $parentSimilarGamesSet = GameSet::firstOrCreate([
            'type' => GameSetType::SimilarGames,
            'game_id' => $parentGame->id,
        ]);

        $existingSimilarGames = GameSetGame::query()
            ->where('game_set_id', $parentSimilarGamesSet->id)
            ->whereIn('game_id', $gameIdsToLink)
            ->pluck('game_id')
            ->toArray();

        $newSetGameIds = array_diff($gameIdsToLink, $existingSimilarGames);
        if (!empty($newSetGameIds)) {
            $parentSimilarGamesSet->games()->attach($newSetGameIds);
        }

        // Link each game's similar games set to include the parent game.
        foreach ($gameIdsToLink as $gameId) {
            $similarGamesSet = GameSet::firstOrCreate([
                'type' => GameSetType::SimilarGames,
                'game_id' => $gameId,
            ]);

            $isAlreadyAttached = GameSetGame::query()
                ->where('game_set_id', $similarGamesSet->id)
                ->where('game_id', $parentGame->id)
                ->exists();

            if (!$isAlreadyAttached) {
                $similarGamesSet->games()->attach($parentGame->id);
            }
        }
    }
}
