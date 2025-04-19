<?php

declare(strict_types=1);

namespace App\Platform\Data;

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
     * @param Collection<int, GameTopAchieverData> $topAchievers
     */
    public function __construct(
        public EventData $event,
        public UserPermissionsData $can,
        /** @var GameSetData[] */
        public array $hubs,
        public Collection $followedPlayerCompletions,
        public Collection $playerAchievementChartBuckets,
        public int $numMasters,
        public Collection $topAchievers,
        public ?PlayerGameData $playerGame,
        public ?PlayerGameProgressionAwardsData $playerGameProgressionAwards,
    ) {
    }
}
