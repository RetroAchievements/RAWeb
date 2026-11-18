<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

/**
 * How many players hold each unlock count for one game.
 *
 * The set is split into promoted and unpromoted achievements, because a player
 * can hold a different number of each.
 */
class GameAchievementDistributionMetaData extends Data
{
    public function __construct(
        public AchievementDistributionGroupData $promoted,
        public AchievementDistributionGroupData $unpromoted,
    ) {
    }
}
