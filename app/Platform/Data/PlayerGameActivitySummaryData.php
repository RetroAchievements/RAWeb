<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameActivitySummary')]
class PlayerGameActivitySummaryData extends Data
{
    public function __construct(
        public float $achievementPlaytime,
        public int $achievementSessionCount,
        public float $generatedSessionAdjustment,
        public int $totalUnlockTime,
        public float $totalPlaytime,
    ) {
    }
}
