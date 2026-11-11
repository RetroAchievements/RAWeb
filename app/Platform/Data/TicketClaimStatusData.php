<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class TicketClaimStatusData extends Data
{
    public function __construct(
        public bool $isClaimable,
        public bool $isWarned,
        public ?Carbon $warnAt,
        public ?Carbon $expiresAt,
    ) {
    }
}
