<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum LeaderboardState: string
{
    /** The leaderboard is currently active and accepting entries. */
    case Active = "active";

    /** The leaderboard is disabled and not accepting new entries. */
    case Disabled = "disabled";

    /** The leaderboard is unpublished and not counted in official stats. */
    case Unpublished = "unpublished";
}
