<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\User;
use App\Platform\Events\PlayerMetricsUpdated;
use Illuminate\Support\Facades\DB;

class UpdatePlayerMetricsAction
{
    public function execute(User $user): void
    {
        $playerGames = $user->playerGames()
            ->join('games', 'games.id', '=', 'player_games.game_id')
            ->whereNotIn('games.system_id', [System::Hubs, System::Events])
            ->where('achievements_unlocked', '>', 0);
        $user->achievements_unlocked = $playerGames->sum('player_games.achievements_unlocked');
        $user->achievements_unlocked_hardcore = $playerGames->sum('player_games.achievements_unlocked_hardcore');
        $user->completion_percentage_average = $playerGames->average('player_games.completion_percentage');
        $user->completion_percentage_average_hardcore = $playerGames->average('player_games.completion_percentage_hardcore');

        $keptPointTotals = $this->sumPointsKeptFromDemotions($user);

        $user->points_hardcore = $playerGames->sum('player_games.points_hardcore') + $keptPointTotals['points_hardcore'];
        $user->points = $playerGames->sum('player_games.points') + $keptPointTotals['points'] - $user->points_hardcore;
        $user->points_weighted = $playerGames->sum('player_games.points_weighted');

        DB::transaction(fn () => $user->saveQuietly(), attempts: 3);

        PlayerMetricsUpdated::dispatch($user);
    }

    /**
     * Retained points only feed the raw and hardcore totals - not weighted points!
     * If they also fed into weighted points, it may lead to runaway weighted points.
     *
     * @return array{points: int, points_hardcore: int}
     */
    private function sumPointsKeptFromDemotions(User $owner): array
    {
        $pointTotals = PlayerAchievement::query()
            ->retainedFromDemotion()
            ->where('player_achievements.user_id', $owner->id)
            ->selectRaw('SUM(achievements.points) AS kept_points')
            ->selectRaw('SUM(CASE WHEN player_achievements.unlocked_hardcore_at < achievements.demoted_at THEN achievements.points ELSE 0 END) AS kept_points_hardcore')
            ->toBase()
            ->first();

        return [
            'points' => (int) ($pointTotals->kept_points ?? 0),
            'points_hardcore' => (int) ($pointTotals->kept_points_hardcore ?? 0),
        ];
    }
}
