<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

/**
 * One point of a distribution.
 *
 * It gives the number of players who unlocked exactly this many achievements.
 */
class AchievementDistributionPointData extends Data
{
    public function __construct(
        public int $unlockCount,
        public int $playersHardcore,
        public int $playersCasual,
    ) {
    }
}
