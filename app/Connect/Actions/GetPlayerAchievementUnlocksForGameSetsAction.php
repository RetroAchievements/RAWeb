<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;

class GetPlayerAchievementUnlocksForGameSetsAction
{
    /**
     * Get player achievement unlocks for all achievement sets associated with a game.
     * Handles multi-parent subsets by finding all related games through shared achievement sets.
     *
     * @return array<int, array{DateEarned?: string, DateEarnedHardcore?: string}>
     */
    public function execute(
        User $user,
        Game $game,
        AchievementFlag $flag = AchievementFlag::OfficialCore
    ): array {
        $achievementSetIds = $game->gameAchievementSets()->pluck('achievement_set_id');

        if ($achievementSetIds->isEmpty()) {
            return [];
        }

        // Find all games that share these achievement sets in a single query.
        // This includes the game itself, sibling games, and parent games.
        $relatedGameIds = GameAchievementSet::whereIn('achievement_set_id', $achievementSetIds)
            ->pluck('game_id')
            ->unique();

        // Fetch player unlocks for achievements from all related games.
        return $user
            ->playerAchievements()
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->whereIn('Achievements.GameID', $relatedGameIds)
            ->where('Achievements.Flags', $flag->value)
            ->orderBy('player_achievements.achievement_id')
            ->get([
                'player_achievements.achievement_id',
                'player_achievements.unlocked_at',
                'player_achievements.unlocked_hardcore_at',
            ])
            ->mapWithKeys(fn ($unlock) => [
                $unlock->achievement_id => $this->formatUnlockDates($unlock),
            ])
            ->toArray();
    }

    /**
     * Format unlock dates for API response.
     *
     * @return array{DateEarned?: string, DateEarnedHardcore?: string}
     */
    private function formatUnlockDates(object $unlock): array
    {
        $result = [];

        if ($unlock->unlocked_at) {
            $result['DateEarned'] = $unlock->unlocked_at->__toString();
        }

        if ($unlock->unlocked_hardcore_at) {
            $result['DateEarnedHardcore'] = $unlock->unlocked_hardcore_at->__toString();
        }

        return $result;
    }
}
