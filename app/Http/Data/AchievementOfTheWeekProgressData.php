<?php

declare(strict_types=1);

namespace App\Http\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementOfTheWeekProgress')]
class AchievementOfTheWeekProgressData extends Data
{
    public function __construct(
        public int $streakLength,
        public bool $hasCurrentWeek,
        public bool $hasActiveStreak,
    ) {
    }
}
