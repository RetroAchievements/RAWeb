<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Represents a bucket of achievement unlock data for chart visualization.
 * Each bucket contains a range of players who have unlocked a specific number
 * of achievements within a game, divided by unlock mode (softcore/hardcore).
 */
#[TypeScript('PlayerAchievementChartBucket')]
class PlayerAchievementChartBucketData extends Data
{
    public function __construct(
        /** The lower bound of this achievement count bucket (inclusive) */
        public int $start,
        /** The upper bound of this achievement count bucket (inclusive) */
        public int $end,
        /** Number of players who have unlocked this many achievements in softcore mode */
        public int $softcore,
        /** Number of players who have unlocked this many achievements in hardcore mode */
        public int $hardcore,
    ) {
    }
}
