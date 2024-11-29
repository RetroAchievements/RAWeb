<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Platform\Data\GameData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TrendingGame')]
class TrendingGameData extends Data
{
    public function __construct(
        public GameData $game,
        public int $playerCount,
    ) {
    }
}
