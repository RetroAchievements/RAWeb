<?php

declare(strict_types=1);

namespace App\Http\Data;

use App\Platform\Data\EventAchievementData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementOfTheWeekProps')]
class AchievementOfTheWeekPropsData extends Data
{
    public function __construct(
        public EventAchievementData $currentEventAchievement,
        public bool $doesUserHaveUnlock,
    ) {
    }
}
