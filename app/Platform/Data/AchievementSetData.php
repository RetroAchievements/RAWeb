<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementSet')]
class AchievementSetData extends Data
{
    public function __construct(
        public int $id,
        public int $playersTotal,
        public int $playersHardcore,
        public int $achievementsPublished,
        public int $achievementsUnpublished,
        public int $pointsTotal,
        public int $pointsWeighted,
        public string $imageAssetPathUrl,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
        /** @var AchievementData[] */
        public array $achievements,
    ) {
    }
}
