<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Platform\Enums\TicketListFilterKind;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use stdClass;

#[TypeScript('TicketListFilter')]
class TicketListFilterData extends Data
{
    /**
     * @param list<string> $values
     */
    public function __construct(
        public TicketListFilterKind $kind,
        #[LiteralTypeScriptType('string[]')]
        public array $values,
        #[LiteralTypeScriptType('Record<string, string>')]
        public stdClass $valueLabels,
        public bool $isFreeText = false,
    ) {
    }
}
