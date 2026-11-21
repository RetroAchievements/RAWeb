<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketAlreadyFixedEvidence')]
class TicketAlreadyFixedEvidenceData extends Data
{
    public function __construct(
        public Carbon $reporterSessionStartedAt,
        public Carbon $logicChangedAt,
    ) {
    }
}
