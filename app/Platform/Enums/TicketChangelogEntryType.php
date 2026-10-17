<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketChangelogEntryType: string
{
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Requested = 'requested';
    case Returned = 'returned';
    case Approved = 'approved';
    case Reopened = 'reopened';
}
