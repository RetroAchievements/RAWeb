<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

class AchievementDistributionGroupData extends Data
{
    public function __construct(
        public int $totalAchievements,

        /**
         * @var array<AchievementDistributionPointData>
         */
        public array $distribution,
    ) {
    }
}
