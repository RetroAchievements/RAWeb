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
            ->whereNotIn('games.system_id', [System::Hubs, System::Events]) // hubs and events never count
            ->where('achievements_unlocked', '>', 0);
        $user->achievements_unlocked = $playerGames->sum('player_games.achievements_unlocked');
        $user->achievements_unlocked_hardcore = $playerGames->sum('player_games.achievements_unlocked_hardcore');
        $user->completion_percentage_average = $playerGames->average('player_games.completion_percentage');
        $user->completion_percentage_average_hardcore = $playerGames->average('player_games.completion_percentage_hardcore');

        $retainedPointSums = $this->sumKeptPointsFromDemotions($user);

        $user->points_hardcore = $playerGames->sum('player_games.points_hardcore') + $retainedPointSums['points_hardcore'];
        $user->points = $playerGames->sum('player_games.points') + $retainedPointSums['points'] - $user->points_hardcore;
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
    private function sumKeptPointsFromDemotions(User $player): array
    {
        $totals = PlayerAchievement::query()
            ->retainedFromDemotion()
            ->where('player_achievements.user_id', $player->id)
            ->selectRaw('SUM(achievements.points) AS points')
            ->selectRaw('SUM(CASE WHEN player_achievements.unlocked_hardcore_at < achievements.demoted_at THEN achievements.points ELSE 0 END) AS points_hardcore')
            ->toBase()
            ->first();

        return [
            'points' => (int) ($totals->points ?? 0),
            'points_hardcore' => (int) ($totals->points_hardcore ?? 0),
        ];
    }
}
