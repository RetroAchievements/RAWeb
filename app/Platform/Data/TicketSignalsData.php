<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketSignal;
use App\Community\Enums\TicketSignalVerdict;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketSignals')]
class TicketSignalsData extends Data
{
    public function __construct(
        public TicketSignalVerdict $verdict,
        /** @var TicketSignal[] */
        public array $kinds,
        public int $unlockCount,
        public Carbon $windowStartedAt,
        public bool $hasRecentLogicChange, // the window opens at a logic change rather than the 90-day cap
        public ?Carbon $reporterEarnedAt, // only set alongside one of the three "earned" kinds
    ) {
    }
}
