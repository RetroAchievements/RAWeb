<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;
use Illuminate\Support\Facades\DB;

// Recalculates the number of players for a game.
class UpdateGamePlayerCountAction
{
    public function execute(Game $game, bool $shouldRecalculateAchievementUnlockCounts = true): void
    {
        $coreGameAchievementSet = $game->gameAchievementSets()->core()->first();

        $unrankedUserFilter = function ($query) {
            $query->selectRaw('1')
                ->from('unranked_users')
                ->whereColumn('unranked_users.user_id', 'player_games.user_id');
        };

        $totalPlayersQuery = PlayerGame::query()
            ->where('player_games.game_id', $game->id)
            ->where('player_games.achievements_unlocked', '>', 0)
            ->whereNotExists($unrankedUserFilter);

        $hardcorePlayersQuery = PlayerGame::query()
            ->where('player_games.game_id', $game->id)
            ->where('player_games.achievements_unlocked_hardcore', '>', 0)
            ->whereNotExists($unrankedUserFilter);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $totalPlayersQuery->forceIndex('player_games_game_id_achievements_unlocked_index');
            $hardcorePlayersQuery->forceIndex('player_games_game_id_achievements_unlocked_hardcore_index');
        }

        [$game->players_total, $game->players_hardcore] = DB::transaction(fn (): array => [
            $totalPlayersQuery->count(),
            $hardcorePlayersQuery->count(),
        ]);

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
