<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameSet;
use App\Platform\Enums\GameSetType;

class UnlinkSimilarGamesAction
{
    public function execute(Game $parentGame, array $gameIdsToUnlink): void
    {
        // Remove bidirectional alternative game links.
        foreach ($gameIdsToUnlink as $gameId) {
            GameAlternative::where([
                'gameID' => $parentGame->id,
                'gameIDAlt' => $gameId,
            ])->delete();

            GameAlternative::where([
                'gameID' => $gameId,
                'gameIDAlt' => $parentGame->id,
            ])->delete();
        }

        $parentSimilarGamesSet = GameSet::where([
            'type' => GameSetType::SimilarGames,
            'game_id' => $parentGame->id,
        ])->first();
        if ($parentSimilarGamesSet) {
            $parentSimilarGamesSet->games()->detach($gameIdsToUnlink);
        }

        // Remove parent game from each game's similar games set.
        foreach ($gameIdsToUnlink as $gameId) {
            $similarGamesSet = GameSet::where([
                'type' => GameSetType::SimilarGames,
                'game_id' => $gameId,
            ])->first();

            if ($similarGamesSet) {
                $similarGamesSet->games()->detach($parentGame->id);
            }
        }
    }
}
