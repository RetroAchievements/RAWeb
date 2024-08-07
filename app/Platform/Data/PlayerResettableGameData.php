<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerResettableGame')]
class PlayerResettableGameData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $consoleName,
        public int $numAwarded,
        public int $numPossible,
    ) {
    }
}
