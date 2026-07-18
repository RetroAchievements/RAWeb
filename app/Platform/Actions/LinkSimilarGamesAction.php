<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Exceptions\SimilarGamesCapExceededException;
use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use Illuminate\Support\Facades\DB;

class LinkSimilarGamesAction
{
    public const int MAX_SIMILAR_GAMES = 6;

    public function execute(Game $parentGame, array $gameIdsToLink): void
    {
        // normalize to a list of ints and drop any self-link attempt
        $gameIdsToLink = array_values(array_unique(array_map('intval', $gameIdsToLink)));
        $gameIdsToLink = array_values(array_filter(
            $gameIdsToLink,
            fn (int $id): bool => $id !== $parentGame->id,
        ));

        $parentSimilarGamesSet = GameSet::firstOrNew([
            'type' => GameSetType::SimilarGames,
            'game_id' => $parentGame->id,
        ]);

        $existingParentLinkedIds = $parentSimilarGamesSet->exists
            ? $parentSimilarGamesSet->games()->pluck('games.id')->all()
            : [];

        $newSetGameIds = array_values(array_diff($gameIdsToLink, $existingParentLinkedIds));

        $existingParentCount = count($existingParentLinkedIds);
        if ($existingParentCount + count($newSetGameIds) > self::MAX_SIMILAR_GAMES) {
            throw new SimilarGamesCapExceededException($parentGame->id, self::MAX_SIMILAR_GAMES);
        }

        /** @var GameSet[] $targetSets */
        $targetSets = [];
        foreach ($gameIdsToLink as $gameId) {
            $candidateSet = GameSet::firstOrNew([
                'type' => GameSetType::SimilarGames,
                'game_id' => $gameId,
            ]);

            if ($candidateSet->exists && $candidateSet->games()->where('games.id', $parentGame->id)->exists()) {
                continue;
            }

            $candidateCount = $candidateSet->exists ? $candidateSet->games()->count() : 0;
            if ($candidateCount + 1 > self::MAX_SIMILAR_GAMES) {
                throw new SimilarGamesCapExceededException($gameId, self::MAX_SIMILAR_GAMES);
            }

            $targetSets[] = $candidateSet;
        }

        if (empty($newSetGameIds) && empty($targetSets)) {
            return;
        }

        // writes only happen if validation passed
        DB::transaction(function () use ($parentGame, $parentSimilarGamesSet, $newSetGameIds, $targetSets): void {
            if (!empty($newSetGameIds)) {
                if (!$parentSimilarGamesSet->exists) {
                    $parentSimilarGamesSet->save();
                }
                $parentSimilarGamesSet->games()->attach($newSetGameIds);
            }

            foreach ($targetSets as $candidateSet) {
                if (!$candidateSet->exists) {
                    $candidateSet->save();
                }
                $candidateSet->games()->attach($parentGame->id);
            }
        });
    }
}
