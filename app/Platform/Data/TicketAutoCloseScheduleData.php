<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class TicketAutoCloseScheduleData extends Data
{
    public function __construct(
        public bool $isReminded,
        public Carbon $remindAt,
        public Carbon $closeAt,
    ) {
    }
}
