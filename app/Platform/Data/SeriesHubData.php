<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('SeriesHub')]
class SeriesHubData extends Data
{
    /**
     * @param GameData[] $topGames
     */
    public function __construct(
        public GameSetData $hub,
        public int $totalGameCount,
        public int $achievementsPublished,
        public int $pointsTotal,
        public array $topGames,
        public int $additionalGameCount,
    ) {
    }
}
