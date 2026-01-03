<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\AchievementGroup;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementSetGroup')]
class AchievementSetGroupData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public int $orderColumn,
        public int $achievementCount = 0,
        public ?string $badgeUrl = null,
    ) {
    }

    public static function fromAchievementGroup(AchievementGroup $group): self
    {
        return new self(
            id: $group->id,
            label: $group->label,
            orderColumn: $group->order_column,
            achievementCount: $group->achievements_count ?? 0,
            badgeUrl: $group->representative_badge_url ?? null,
        );
    }
}
