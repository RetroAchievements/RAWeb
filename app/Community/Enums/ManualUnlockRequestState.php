<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ManualUnlockRequestState: string
{
    /**
     * Used for approved requests
     */
    case Approved = "approved";

    /**
     * Used for denied requests
     */
    case Denied = "denied";

    /**
     * Used for pending requests
     */
    case Pending = "pending";

    /**
     * Used for partially approved requests
     */
    case Partial = "partial";

    /**
     * Used for requests requesting more details
     */
    case Requesting = "requesting";

}
