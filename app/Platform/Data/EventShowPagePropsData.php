<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Data\CommentData;
use App\Data\UserPermissionsData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EventShowPageProps')]
class EventShowPagePropsData extends Data
{
    /**
     * @param Collection<int, FollowedPlayerCompletionData> $followedPlayerCompletions
     * @param Collection<int, PlayerAchievementChartBucketData> $playerAchievementChartBuckets
     * @param Collection<int, CommentData> $recentVisibleComments
     * @param Collection<int, GameTopAchieverData> $topAchievers
     * @param GameSetData[] $breadcrumbs Ordered array of hubs from root to current event's primary hub
     */
    public function __construct(
        public EventData $event,
        public UserPermissionsData $can,
        /** @var GameSetData[] */
        public array $hubs,
        public array $breadcrumbs,
        public Collection $followedPlayerCompletions,
        public bool $isSubscribedToComments,
        public int $numComments,
        public int $numMasters,
        public Collection $playerAchievementChartBuckets,
        public Collection $recentVisibleComments,
        public Collection $topAchievers,
        public ?PlayerGameData $playerGame,
        public ?PlayerGameProgressionAwardsData $playerGameProgressionAwards,
    ) {
    }
}
