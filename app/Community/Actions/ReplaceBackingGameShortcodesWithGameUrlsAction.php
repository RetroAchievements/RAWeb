<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;

class ReplaceBackingGameShortcodesWithGameUrlsAction
{
    /**
     * Reverse backing game shortcodes to full URLs for editing.
     *
     * Only does the reversal if:
     * - The game is a backing game (has a Core achievement set).
     * - Exactly ONE parent game uses that set as non-Core.
     *
     * Otherwise, the action leaves the shortcode unchanged to avoid ambiguity.
     *
     * Example: [game=29895] becomes https://retroachievements.org/game2/668?set=9534
     */
    public function execute(string $messageBody): string
    {
        // Extract all game IDs from [game=X] shortcodes.
        // We'll do batch queries to avoid N+1 waterfalls.
        preg_match_all('/\[game=(\d+)\]/', $messageBody, $matches);
        $gameIds = array_unique($matches[1]);

        if (empty($gameIds)) {
            return $messageBody;
        }

        // Find which of these games are backing games (have Core achievement sets).
        $backingGameSets = GameAchievementSet::whereIn('game_id', $gameIds)
            ->where('type', AchievementSetType::Core)
            ->get(['game_id', 'achievement_set_id']);

        if ($backingGameSets->isEmpty()) {
            return $messageBody;
        }

        // For each backing game's Core set, find if EXACTLY ONE parent game uses it as non-Core.
        $setIds = $backingGameSets->pluck('achievement_set_id')->unique();

        // Query for all games that use these sets as non-Core types.
        $parentGameSets = GameAchievementSet::whereIn('achievement_set_id', $setIds)
            ->where('type', '!=', AchievementSetType::Core)
            ->get(['game_id', 'achievement_set_id'])
            ->groupBy('achievement_set_id');

        // Build a map: backingGameId -> [parentGameId, setId].
        // Only include entries where exactly one parent game exists.
        $conversionMap = [];
        foreach ($backingGameSets as $backingSet) {
            $setId = $backingSet->achievement_set_id;
            $backingGameId = $backingSet->game_id;

            $parentGames = $parentGameSets->get($setId);

            // Only convert if exactly one parent game exists.
            if ($parentGames && $parentGames->count() === 1) {
                $conversionMap[$backingGameId] = [
                    'parentGameId' => $parentGames->first()->game_id,
                    'setId' => $setId,
                ];
            }
        }

        if (empty($conversionMap)) {
            return $messageBody;
        }

        // Replace backing game shortcodes with full URLs.
        return preg_replace_callback('/\[game=(\d+)\]/', function ($matches) use ($conversionMap) {
            $gameId = $matches[1];

            if (isset($conversionMap[$gameId])) {
                $parentGameId = $conversionMap[$gameId]['parentGameId'];
                $setId = $conversionMap[$gameId]['setId'];

                return route('game.show', ['game' => $parentGameId, 'set' => $setId]);
            }

            return $matches[0]; // leave unchanged
        }, $messageBody);
    }
}
