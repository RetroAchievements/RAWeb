<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameListSortField: string
{
    case AchievementsPublished = 'achievementsPublished';
    case HasActiveOrInReviewClaims = 'hasActiveOrInReviewClaims';
    case LastUpdated = 'lastUpdated';
    case NumRequests = 'numRequests';
    case NumUnresolvedTickets = 'numUnresolvedTickets';
    case NumVisibleLeaderboards = 'numVisibleLeaderboards';
    case PlayersTotal = 'playersTotal';
    case PointsTotal = 'pointsTotal';
    case Progress = 'progress';
    case ReleasedAt = 'releasedAt';
    case RetroRatio = 'retroRatio';
    case System = 'system';
    case Title = 'title';
}
