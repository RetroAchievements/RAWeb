<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\User;
use App\Platform\Events\PlayerMetricsUpdated;

class UpdatePlayerMetricsAction
{
    public function execute(User $user): void
    {
        $playerGames = $user->playerGames()
            ->join('GameData', 'GameData.ID', '=', 'player_games.game_id')
            ->whereNotIn('GameData.ConsoleID', [100, 101]) // ignore events and hubs
            ->where('achievements_unlocked', '>', 0);
        $user->achievements_unlocked = $playerGames->sum('achievements_unlocked');
        $user->achievements_unlocked_hardcore = $playerGames->sum('achievements_unlocked_hardcore');
        $user->completion_percentage_average = $playerGames->average('completion_percentage');
        $user->completion_percentage_average_hardcore = $playerGames->average('completion_percentage_hardcore');
        $user->points = $playerGames->sum('points_hardcore');
        $user->points_softcore = $playerGames->sum('points') - $user->points;
        $user->points_weighted = $playerGames->sum('points_weighted');

        $user->saveQuietly();

        PlayerMetricsUpdated::dispatch($user);
    }
}
