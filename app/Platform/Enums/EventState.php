<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum EventState: string
{
    /** The event is currently in progress. */
    case Active = "active";

    /** The event is done, and progress cannot be made. */
    case Concluded = "concluded";

    /** The event has no end. Progress can always be made. */
    case Evergreen = "evergreen";
}
