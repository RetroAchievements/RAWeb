<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Filament\Support\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ManualUnlockRequestItemState: string implements HasLabel
{
    /**
     * Used for approved achievement requests
     */
    case Approved = "approved";

    /**
     * Used for denied achievement requests
     */
    case Denied = "denied";

    /**
     * Used for pending achievement requests
     */
    case Pending = "pending";

    /**
     * Used for achievement requests requesting feedback
     */
    case Request = "request";

    public function getLabel(): string
    {
        return match ($this) {
            ManualUnlockRequestItemState::Approved => 'Approved',
            ManualUnlockRequestItemState::Denied => 'Denied',
            ManualUnlockRequestItemState::Pending => 'Pending',
            ManualUnlockRequestItemState::Request => 'Request',
        };
    }
}
