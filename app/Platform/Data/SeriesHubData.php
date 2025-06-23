<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('SeriesHub')]
class SeriesHubData extends Data
{
    public function __construct(
        public GameSetData $hub,
        public int $totalGameCount,
        public int $gamesWithAchievementsCount,
        public int $achievementsPublished,
        public int $pointsTotal,
    ) {
    }
}
