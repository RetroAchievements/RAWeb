<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameListEntry')]
class GameListEntryData extends Data
{
    public function __construct(
        public GameData $game,
        public ?PlayerGameData $playerGame,
        public ?bool $isInBacklog,
    ) {
    }
}
