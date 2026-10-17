<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketResolution;
use App\Data\UserData;
use App\Platform\Enums\TicketChangelogEntryType;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketChangelogEntry')]
class TicketChangelogEntryData extends Data
{
    public function __construct(
        public TicketChangelogEntryType $type,
        public Carbon $createdAt,
        public ?UserData $user = null,
        public ?TicketResolution $resolution = null,
    ) {
    }
}
