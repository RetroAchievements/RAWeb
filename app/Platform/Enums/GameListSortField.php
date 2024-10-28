<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameListSortField: string
{
    case Title = 'title';
    case System = 'system';
    case AchievementsPublished = 'achievementsPublished';
    case PointsTotal = 'pointsTotal';
    case RetroRatio = 'retroRatio';
    case LastUpdated = 'lastUpdated';
    case ReleasedAt = 'releasedAt';
    case PlayersTotal = 'playersTotal';
    case NumVisibleLeaderboards = 'numVisibleLeaderboards';
    case NumUnresolvedTickets = 'numUnresolvedTickets';
    case Progress = 'progress';
}
