<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\PlayerMetricsUpdated;
use App\Site\Models\User;

class UpdatePlayerMetrics
{
    public function execute(User $user): void
    {
        $playerGames = $user->playerGames()->where('achievements_unlocked', '>', 0);
        $user->achievements_unlocked = $playerGames->sum('achievements_unlocked');
        $user->achievements_unlocked_hardcore = $playerGames->sum('achievements_unlocked_hardcore');
        $user->completion_percentage_average = $playerGames->average('completion_percentage');
        $user->completion_percentage_average_hardcore = $playerGames->average('completion_percentage_hardcore');

        // TODO refactor to use aggregated player_games metrics
        $user->RAPoints = $user->achievements()->published()->wherePivotNotNull('unlocked_hardcore_at')->sum('Points');
        $user->RASoftcorePoints = $user->achievements()->published()->wherePivotNull('unlocked_hardcore_at')->sum('Points');
        $user->TrueRAPoints = $user->achievements()->published()->wherePivotNotNull('unlocked_hardcore_at')->sum('TrueRatio');

        $user->save();

        PlayerMetricsUpdated::dispatch($user);
    }
}
