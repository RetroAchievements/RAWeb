<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketListSortField: string
{
    case CreatedAt = 'createdAt';
    case State = 'state';
    case ResolvedAt = 'resolvedAt';
}
