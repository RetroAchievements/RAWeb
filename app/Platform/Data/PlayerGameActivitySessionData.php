<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Enums\PlayerGameActivitySessionType;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameActivitySession')]
class PlayerGameActivitySessionData extends Data
{
    public function __construct(
        public PlayerGameActivitySessionType $type,
        public Carbon $startTime,
        public Carbon $endTime,
        public int $duration,
        public ?string $userAgent = null,
        public ?ParsedUserAgentData $parsedUserAgent = null,
        public ?GameHashData $gameHash = null,
        /** @var PlayerGameActivityEventData[] */
        public array $events = [],
    ) {
    }
}
