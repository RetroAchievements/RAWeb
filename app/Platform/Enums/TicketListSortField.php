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

    /**
     * Groups the rows by achievement or leaderboard. Descending puts the groups with the
     * most tickets first, whereas ascending orders them A to Z.
     */
    case Ticketable = 'ticketable';

    /**
     * Groups the rows by game. Descending puts the games with the most tickets first,
     * whereas ascending orders them A to Z.
     */
    case Game = 'game';
}
