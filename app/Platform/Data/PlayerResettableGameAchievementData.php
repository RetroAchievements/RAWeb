<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerResettableGameAchievement')]
class PlayerResettableGameAchievementData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public int $points,
        public bool $isHardcore,
    ) {
    }
}
