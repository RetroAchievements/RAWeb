<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketReporterUnlock')]
class TicketReporterUnlockData extends Data
{
    public function __construct(
        public Carbon $unlockedAt,
        public bool $isHardcore,
        public ?UserData $unlocker, // non-null if it's a manual unlock
    ) {
    }
}
