<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameActivity')]
class PlayerGameActivityData extends Data
{
    public function __construct(
        public PlayerGameActivitySummaryData $summarizedActivity,

        /** @var PlayerGameActivitySessionData[] */
        public array $sessions,

        /** @var PlayerGameClientBreakdownData[] */
        public array $clientBreakdown,
    ) {
    }
}
