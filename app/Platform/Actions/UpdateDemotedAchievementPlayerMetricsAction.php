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
    private const UNLOCK_ROWS_PER_CHUNK = 1000;

    public function execute(Achievement $achievement): void
    {
        if (!$achievement->should_keep_demoted_points) {
            return;
        }

        PlayerAchievement::query()
            ->where('achievement_id', $achievement->id)
            ->where('unlocked_at', '<', $achievement->demoted_at)
            ->select(['id', 'user_id'])
            ->chunkById(self::UNLOCK_ROWS_PER_CHUNK, function (Collection $unlockBatch): void {
                foreach ($unlockBatch as $unlockRow) {
                    dispatch(new UpdatePlayerMetricsJob($unlockRow->user_id))->onQueue('player-metrics');
                }
            });
    }
}
