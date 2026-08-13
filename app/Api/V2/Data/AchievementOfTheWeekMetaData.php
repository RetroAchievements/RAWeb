<?php

declare(strict_types=1);

namespace App\Api\V2\Data;

use Spatie\LaravelData\Data;

class AchievementOfTheWeekMetaData extends Data
{
    public function __construct(
        public CurrentEventAchievementData $eventAchievement,
    ) {
    }
}
