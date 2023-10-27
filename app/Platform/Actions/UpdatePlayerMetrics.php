<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\PlayerMetricsUpdated;
use App\Site\Models\User;

class UpdatePlayerMetrics
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

        if (config('feature.aggregate_queries')) {
            $user->RAPoints = $playerGames->sum('points_hardcore');
            $user->RASoftcorePoints = $playerGames->sum('points') - $user->RAPoints;
            $user->TrueRAPoints = $playerGames->sum('points_weighted');
        } else {
            $user->RAPoints = $user->achievements()->published()->wherePivotNotNull('unlocked_hardcore_at')->sum('Points');
            $user->RASoftcorePoints = $user->achievements()->published()->wherePivotNull('unlocked_hardcore_at')->sum('Points');
            $user->TrueRAPoints = $user->achievements()->published()->wherePivotNotNull('unlocked_hardcore_at')->sum('TrueRatio');
        }

        $user->save();

        PlayerMetricsUpdated::dispatch($user);
    }
}
