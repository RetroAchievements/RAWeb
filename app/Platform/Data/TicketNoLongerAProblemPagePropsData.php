<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketNoLongerAProblemPageProps')]
class TicketNoLongerAProblemPagePropsData extends Data
{
    public function __construct(
        public string $ticketableTitle,
        public string $gameTitle,
        public bool $canClose,
        public string $closeUrl,
    ) {
    }
}
