<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Data\CommentData;
use App\Data\UserPermissionsData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameShowPageProps')]
class GameShowPagePropsData extends Data
{
    /**
     * @param Collection<int, AchievementSetClaimData> $achievementSetClaims
     * @param Collection<int, CommentData> $recentVisibleComments
     * @param Collection<int, FollowedPlayerCompletionData> $followedPlayerCompletions
     * @param Collection<int, GameTopAchieverData> $topAchievers
     * @param Collection<int, PlayerAchievementChartBucketData> $playerAchievementChartBuckets
     */
    public function __construct(
        public Collection $achievementSetClaims,
        public AggregateAchievementSetCreditsData $aggregateCredits,
        public GameData $game,
        public UserPermissionsData $can,
        public bool $hasMatureContent,
        /** @var GameSetData[] */
        public array $hubs,
        public bool $isLockedOnlyFilterEnabled,
        public bool $isMissableOnlyFilterEnabled,
        public bool $isOnWantToDevList,
        public bool $isOnWantToPlayList,
        public bool $isSubscribedToComments,
        public Collection $followedPlayerCompletions,
        public Collection $playerAchievementChartBuckets,
        public int $numComments,
        public int $numCompatibleHashes,
        public int $numMasters,
        public int $numOpenTickets,
        public Collection $recentVisibleComments,
        /** @var GameData[] */
        public array $similarGames,
        public Collection $topAchievers,
        public ?PlayerGameData $playerGame,
        public ?PlayerGameProgressionAwardsData $playerGameProgressionAwards,
        public ?SeriesHubData $seriesHub,
    ) {
    }
}
