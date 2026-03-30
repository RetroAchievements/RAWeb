<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ManualUnlockRequestState: string
{
    case Approved = "approved";

    case Denied = "denied";

    case Pending = "pending";

    /**
     * Used for partially approved requests
     */
    case PartiallyApproved = "partially-approved";

    /**
     * Used for requests needing more details from the requester
     */
    case NeedsMoreDetails = "needs-more-details";
}
