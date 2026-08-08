<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ActiveEventAchievement')]
class ActiveEventAchievementData extends Data
{
    public function __construct(
        public int $achievementId,
        public string $link,
        public string $summary,
        public bool $userUnlocked = false,
    ) {
    }
}
