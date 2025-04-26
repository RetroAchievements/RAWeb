<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameAchievementSet')]
class GameAchievementSetData extends Data
{
    public function __construct(
        public int $id,
        public AchievementSetType $type,
        public ?string $title,
        public int $orderColumn,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
        public AchievementSetData $achievementSet,
    ) {
    }
}
