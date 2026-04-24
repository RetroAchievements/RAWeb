<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementSet')]
class AchievementSetData extends Data
{
    public function __construct(
        public int $id,
        public Lazy|Carbon|null $achievementsFirstPublishedAt,
        public int $achievementsPublished = 0,
        public int $achievementsUnpublished = 0,
        public string $imageAssetPathUrl = '',
        public Lazy|int $medianTimeToComplete = 0,
        public Lazy|int $medianTimeToCompleteHardcore = 0,
        public int $playersHardcore = 0,
        public int $playersTotal = 0,
        public int $pointsTotal = 0,
        public int $pointsWeighted = 0,
        public Lazy|int $timesCompleted = 0,
        public Lazy|int $timesCompletedHardcore = 0,
        public ?Carbon $createdAt = null,
        public ?Carbon $updatedAt = null,
        /** @var AchievementData[] */
        public array $achievements = [],
        /** @var AchievementSetGroupData[] */
        public Lazy|array $achievementGroups = [],
        public ?string $ungroupedBadgeUrl = null,
    ) {
    }
}
