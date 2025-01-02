<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('DeveloperFeedPageProps<TItems = App.Community.Data.ActivePlayer>')]
class DeveloperFeedPagePropsData extends Data
{
    public function __construct(
        public UserData $developer,
        public int $unlocksContributed,
        public int $pointsContributed,
        public int $awardsContributed,
        public int $leaderboardEntriesContributed,
        public PaginatedData $activePlayers,
        /** @var RecentUnlockData[] */
        public array $recentUnlocks,
        /** @var RecentPlayerBadgeData[] */
        public array $recentPlayerBadges,
        /** @var RecentLeaderboardEntryData[] */
        public array $recentLeaderboardEntries,
    ) {
    }
}
