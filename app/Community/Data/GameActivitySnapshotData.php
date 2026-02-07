<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Enums\TrendingReason;
use App\Platform\Data\EventData;
use App\Platform\Data\GameData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameActivitySnapshot')]
class GameActivitySnapshotData extends Data
{
    public function __construct(
        public GameData $game,
        public int $playerCount,
        public ?TrendingReason $trendingReason = null,
        public ?EventData $event = null,
    ) {
    }
}
