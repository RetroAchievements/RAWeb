<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketListGroupField: string
{
    case None = 'none';
    case Game = 'game';
    case Ticketable = 'ticketable';
}
