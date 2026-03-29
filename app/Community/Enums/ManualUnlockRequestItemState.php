<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Filament\Support\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ManualUnlockRequestItemState: string implements HasLabel
{
    case Approved = "approved";

    case Denied = "denied";

    case Pending = "pending";

    /**
     * Used for items needing more details from the requester
     */
    case NeedsMoreDetails = "needs-more-details";

    public function getLabel(): string
    {
        return $this->name;
    }
}
