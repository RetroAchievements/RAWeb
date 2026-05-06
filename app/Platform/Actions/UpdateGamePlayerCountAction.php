<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerGame;
use App\Platform\Enums\AchievementSetType;

// Recalculates the number of players for a game.
class UpdateGamePlayerCountAction
{
    public function execute(Game $game, bool $shouldRecalculateAchievementUnlockCounts = true): void
    {
        $gameIds = [$game->id];

        $coreGameAchievementSet = $game->gameAchievementSets()->core()->first();
        if ($coreGameAchievementSet) {
            $bonusSubsetAchievementSets = GameAchievementSet::query()
                ->where('achievement_set_id', $coreGameAchievementSet->achievement_set_id)
                ->where('type', AchievementSetType::Bonus)
                ->select('game_id')
                ->get();
            foreach ($bonusSubsetAchievementSets as $bonusSubsetAchievementSet) {
                // if this is a bonus subset, also include the players of the parent game
                $gameIds[] = $bonusSubsetAchievementSet->game_id;
            }
        }

        $playersQuery = PlayerGame::whereIn('game_id', $gameIds)
            ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id');

        if (count($gameIds) > 1) {
            $playersQuery->distinct('player_games.user_id');
        }

        $game->players_total = $playersQuery->clone()
            ->where('achievements_unlocked', '>', 0)
            ->count();

        $game->players_hardcore = $playersQuery->clone()
            ->where('achievements_unlocked_hardcore', '>', 0)
            ->count();

        if ($game->isDirty()) {
            $game->saveQuietly();

            // copy the new player counts to the achievement set
            if ($coreGameAchievementSet) {
                $coreSet = $coreGameAchievementSet->achievementSet;
                $coreSet->players_hardcore = $game->players_hardcore;
                $coreSet->players_total = $game->players_total;
                $coreSet->save();
            }

            // if the player count changed, update unlock percentages and weighted points for all achievements in the set
            app()->make(UpdateGameAchievementsMetricsAction::class)
                ->execute($game, $shouldRecalculateAchievementUnlockCounts);
        }
    }
}
