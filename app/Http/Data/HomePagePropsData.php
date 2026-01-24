<?php

declare(strict_types=1);

namespace App\Http\Data;

use App\Community\Data\GameActivitySnapshotData;
use App\Data\AchievementSetClaimGroupData;
use App\Data\CurrentlyOnlineData;
use App\Data\ForumTopicData;
use App\Data\NewsData;
use App\Data\PaginatedData;
use App\Data\StaticDataData;
use App\Data\StaticGameAwardData;
use App\Platform\Data\GameData;
use Illuminate\Support\Collection;
use Inertia\DeferProp;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('HomePageProps<TItems = App.Community.Data.ActivePlayer>')]
class HomePagePropsData extends Data
{
    /**
     * @param Collection<int, NewsData> $recentNews
     * @param Collection<int, AchievementSetClaimGroupData> $completedClaims
     * @param Collection<int, GameActivitySnapshotData> $trendingGames
     * @param Collection<int, GameActivitySnapshotData> $popularGames
     * @param Collection<int, AchievementSetClaimGroupData> $newClaims
     * @param Collection<int, ForumTopicData> $recentForumPosts
     * @param Collection<int, NewsData>|DeferProp $deferredSiteReleaseNotes
     */
    public function __construct(
        public StaticDataData $staticData,
        public ?AchievementOfTheWeekPropsData $achievementOfTheWeek,
        public ?StaticGameAwardData $mostRecentGameMastered,
        public ?StaticGameAwardData $mostRecentGameBeaten,
        public Collection $recentNews,
        public Collection $completedClaims,
        public CurrentlyOnlineData $currentlyOnline,
        public PaginatedData $activePlayers,
        public Collection $trendingGames,
        public Collection $popularGames,
        public Collection $newClaims,
        public Collection $recentForumPosts,
        public ?string $persistedActivePlayersSearch,
        public ?GameData $userCurrentGame = null,
        public ?int $userCurrentGameMinutesAgo = null,
        public bool $hasSiteReleaseNotes = false,
        public bool $hasUnreadSiteReleaseNote = false,
        public Collection|DeferProp $deferredSiteReleaseNotes = new Collection(),
    ) {
    }
}
