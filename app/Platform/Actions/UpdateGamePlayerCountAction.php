<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;

// Recalculates the number of players for a game.
class UpdateGamePlayerCountAction
{
    public function execute(Game $game, bool $shouldRecalculateAchievementUnlockCounts = true): void
    {
        $coreGameAchievementSet = $game->gameAchievementSets()->core()->first();

        $countExpr = fn (string $column): string => "SUM(CASE WHEN player_games.{$column} > 0 THEN 1 ELSE 0 END)";

        $row = PlayerGame::query()
            ->where('player_games.game_id', $game->id)
            ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id')
            ->selectRaw(sprintf(
                '%s AS total, %s AS hardcore',
                $countExpr('achievements_unlocked'),
                $countExpr('achievements_unlocked_hardcore'),
            ))
            ->first();

        $game->players_total = (int) ($row->total ?? 0);
        $game->players_hardcore = (int) ($row->hardcore ?? 0);

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
