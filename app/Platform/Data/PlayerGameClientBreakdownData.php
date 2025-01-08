<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameClientBreakdown')]
class PlayerGameClientBreakdownData extends Data
{
    public function __construct(
        /** eg: "RALibRetro (1.7.0) - picodrive" */
        public string $clientIdentifier,
        /** The actual user agent strings. */
        public array $agents,
        public int $duration,
        public float $durationPercentage,
    ) {
    }
}
