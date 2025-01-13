<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketableType: string
{
    case Achievement = 'achievement';
    case Leaderboard = 'leaderboard';
    case RichPresence = 'rich-presence';
}
