<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\TicketListFilterKind;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketListFilter')]
class TicketListFilterData extends Data
{
    /**
     * @param string[] $values
     */
    public function __construct(
        public TicketListFilterKind $kind,
        #[LiteralTypeScriptType('string[]')]
        public array $values,
    ) {
    }
}
