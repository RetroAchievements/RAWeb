<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameShowPageProps')]
class GameShowPagePropsData extends Data
{
    /**
     * @param Collection<int, FollowedPlayerCompletionData> $followedPlayerCompletions
     * @param Collection<int, PlayerAchievementChartBucketData> $playerAchievementChartBuckets
     * @param Collection<int, GameTopAchieverData> $topAchievers
     */
    public function __construct(
        public GameData $game,
        /** @var GameSetData[] */
        public array $hubs,
        public Collection $followedPlayerCompletions,
        public Collection $playerAchievementChartBuckets,
        public Collection $topAchievers,
        public ?PlayerGameData $playerGame,
        public ?PlayerGameProgressionAwardsData $playerGameProgressionAwards,
    ) {
    }
}
