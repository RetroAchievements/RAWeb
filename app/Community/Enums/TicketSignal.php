<?php

declare(strict_types=1);

namespace App\Community\Enums;

/**
 * The order of the cases below is their display order.
 *
 * At most one of the three "earned" cases applies to a ticket. What separates
 * them is whether the reporter's unlock ended up using the same trigger logic
 * version as the report that was filed.
 */
enum TicketSignal: string
{
    case ManyRecentUnlocks = 'many-recent-unlocks';
    case EarnedWithSameLogic = 'earned-with-same-logic';
    case EarnedAfterReport = 'earned-after-report';
    case EarnedAfterLogicChange = 'earned-after-logic-change';
    case NoRecentUnlocks = 'no-recent-unlocks';
}
