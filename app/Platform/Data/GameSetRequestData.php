<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSetRequestData')]
class GameSetRequestData extends Data
{
    public function __construct(
        public bool $hasUserRequestedSet,
        public int $totalRequests,
        public int $userRequestsRemaining,
    ) {
    }
}
