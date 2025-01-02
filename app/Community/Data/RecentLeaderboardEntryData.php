<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Platform\Data\GameData;
use App\Platform\Data\LeaderboardData;
use App\Platform\Data\LeaderboardEntryData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('RecentLeaderboardEntry')]
class RecentLeaderboardEntryData extends Data
{
    public function __construct(
        public LeaderboardData $leaderboard,
        public LeaderboardEntryData $leaderboardEntry,
        public GameData $game,
        public UserData $user,
        public Carbon $submittedAt,
    ) {
    }
}
