<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Platform\Jobs\UpdatePlayerMetricsJob;
use Illuminate\Support\Collection;

/**
 * When the points of a demoted achievement change, everyone who unlocked it
 * earlier keeps a different amount. Their totals have to be recalculated.
 */
class UpdateDemotedAchievementPlayerMetricsAction
{
    private const PLAYERS_PER_CHUNK = 1000;

    public function execute(Achievement $achievement): void
    {
        if (!$achievement->retainsPointsForPriorUnlocks()) {
            return;
        }

        PlayerAchievement::query()
            ->retainedFromDemotion()
            ->where('achievements.id', $achievement->id)
            ->select(['player_achievements.id', 'player_achievements.user_id'])
            ->chunkById(self::PLAYERS_PER_CHUNK, function (Collection $playerChunk): void {
                foreach ($playerChunk as $playerUnlock) {
                    dispatch(new UpdatePlayerMetricsJob($playerUnlock->user_id))->onQueue('player-metrics');
                }
            }, 'player_achievements.id', 'id');
    }
}
