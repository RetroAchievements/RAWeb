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
            ->join('games', 'games.id', '=', 'player_games.game_id')
            ->whereNotIn('games.system_id', [100, 101]) // ignore events and hubs
            ->where('achievements_unlocked', '>', 0);
        $user->achievements_unlocked = $playerGames->sum('player_games.achievements_unlocked');
        $user->achievements_unlocked_hardcore = $playerGames->sum('player_games.achievements_unlocked_hardcore');
        $user->completion_percentage_average = $playerGames->average('player_games.completion_percentage');
        $user->completion_percentage_average_hardcore = $playerGames->average('player_games.completion_percentage_hardcore');
        $user->RAPoints = $playerGames->sum('player_games.points_hardcore');
        $user->RASoftcorePoints = $playerGames->sum('player_games.points') - $user->RAPoints;
        $user->TrueRAPoints = $playerGames->sum('player_games.points_weighted');

        $user->saveQuietly();

        PlayerMetricsUpdated::dispatch($user);
    }
}
