<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use InvalidArgumentException;

class ResolveRootGameFromGameAndGameHashAction
{
    /**
     * Resolves the root game for a given game hash and user combination.
     * - For legacy clients or when multiset is globally disabled, uses the hash's game ID directly.
     * - For bonus sets, uses the core game ID.
     * - For specialty/exclusive sets, uses the set's game ID.
     *
     * @param GameHash|null $gameHash the game hash to resolve the root game ID for
     * @param Game|null $game the game to resolve the root game ID for (for legacy clients)
     * @param User|null $user the current user (for multiset resolution)
     * @throws InvalidArgumentException when neither $gameHash nor $game is provided
     */
    public function execute(
        ?GameHash $gameHash = null,
        ?Game $game = null,
        ?User $user = null,
    ): Game {
        if (!$gameHash && !$game) {
            throw new InvalidArgumentException('Either gameHash or game must be provided to resolve the root game ID.');
        }

        // For legacy clients or multi-disc games (where the hash isn't provided), just use the game ID directly.
        if (!$gameHash) {
            return $game;
        }

        $hashGame = $gameHash->game;

        // If there's no user or the current user has multiset globally disabled, use the hash game.
        if (!$user || $user->is_globally_opted_out_of_subsets) {
            return $hashGame;
        }

        // Resolve sets once - we'll use this to determine the core game.
        $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $user);
        if ($resolvedSets->isEmpty()) {
            return $hashGame;
        }

        // Get the core game from the first resolved set.
        $coreSet = $resolvedSets->first();
        $coreGame = Game::find($coreSet->game_id) ?? $hashGame;

        // Look up if this hash game's achievement set is attached as a subset to the core game.
        $hashGameSubsetAttachment = GameAchievementSet::whereGameId($coreGame->id)
            ->whereAchievementSetId($hashGame->gameAchievementSets()->core()->first()?->achievement_set_id)
            ->first();

        $isSpecialtyOrExclusive = in_array($hashGameSubsetAttachment->type, [AchievementSetType::Specialty, AchievementSetType::Exclusive]);
        if ($hashGameSubsetAttachment && $isSpecialtyOrExclusive) {
            // For specialty/exclusive sets, use the subset game ID.
            return $hashGame;
        }

        // For all other cases (including bonus sets), use the core game ID.
        return $coreGame;
    }
}
