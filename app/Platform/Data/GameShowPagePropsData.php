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
     * @param Collection<int, FollowedPlayerCompletionData> $followedPlayerCompletions
     * @param Collection<int, PlayerAchievementChartBucketData> $playerAchievementChartBuckets
     * @param Collection<int, CommentData> $recentVisibleComments
     * @param Collection<int, GameTopAchieverData> $topAchievers
     */
    public function __construct(
        public GameData $game,
        public UserPermissionsData $can,
        /** @var GameSetData[] */
        public array $hubs,
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
