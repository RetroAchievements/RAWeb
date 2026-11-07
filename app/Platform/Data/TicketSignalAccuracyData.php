<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketSignalAccuracy')]
class TicketSignalAccuracyData extends Data
{
    public function __construct(
        public int $ticketCount,
        public int $correctCount,
    ) {
    }
}
