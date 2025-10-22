<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Platform\Data\GameListEntryData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameGroup')]
class GameGroupData extends Data
{
    public function __construct(
        public string $header,

        /** @var GameListEntryData[] */
        public array $games,
    ) {
    }
}
