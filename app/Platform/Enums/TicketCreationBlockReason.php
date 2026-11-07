<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketCreationBlockReason: string
{
    case NoPlaySession = 'no_play_session';
}
