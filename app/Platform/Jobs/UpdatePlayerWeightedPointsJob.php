<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\System;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdatePlayerWeightedPointsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $startId,
        public int $endId,
    ) {
        $this->onQueue('player-points-stats-batch');
    }

    public function handle(): void
    {
        // Update player_games.points_weighted.
        DB::update(<<<SQL
            UPDATE player_games pg
            JOIN (
                SELECT pa.user_id, a.game_id, SUM(a.points_weighted) as weighted_points
                FROM player_achievements pa
                INNER JOIN achievements a ON a.id = pa.achievement_id
                WHERE pa.unlocked_hardcore_at IS NOT NULL
                  AND pa.user_id BETWEEN ? AND ?
                GROUP BY pa.user_id, a.game_id
            ) calc ON calc.user_id = pg.user_id AND calc.game_id = pg.game_id
            SET pg.points_weighted = calc.weighted_points
        SQL, [$this->startId, $this->endId]);

        // Update users.points_weighted.
        DB::update(<<<SQL
            UPDATE users u
            JOIN (
                SELECT pg.user_id, SUM(pg.points_weighted) as total_weighted
                FROM player_games pg
                INNER JOIN games g ON g.id = pg.game_id
                WHERE g.system_id NOT IN (?, ?)
                  AND pg.achievements_unlocked > 0
                  AND pg.user_id BETWEEN ? AND ?
                GROUP BY pg.user_id
            ) calc ON calc.user_id = u.id
            SET u.points_weighted = calc.total_weighted
        SQL, [System::Events, System::Hubs, $this->startId, $this->endId]);
    }
}
