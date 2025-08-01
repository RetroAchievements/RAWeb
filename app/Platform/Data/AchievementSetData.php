<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementSet')]
class AchievementSetData extends Data
{
    public function __construct(
        public int $id,
        public int $achievementsPublished,
        public int $achievementsUnpublished,
        public string $imageAssetPathUrl,
        public Lazy|int $medianTimeToComplete,
        public Lazy|int $medianTimeToCompleteHardcore,
        public int $playersHardcore,
        public int $playersTotal,
        public int $pointsTotal,
        public int $pointsWeighted,
        public Lazy|int $timesCompleted,
        public Lazy|int $timesCompletedHardcore,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
        /** @var AchievementData[] */
        public array $achievements,
    ) {
    }
}
