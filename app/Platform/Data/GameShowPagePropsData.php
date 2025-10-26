<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Data\CommentData;
use App\Data\UserPermissionsData;
use App\Platform\Enums\GamePageListSort;
use App\Platform\Enums\GamePageListView;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\AutoInertiaDeferred;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameShowPageProps')]
class GameShowPagePropsData extends Data
{
    /**
     * @param Collection<int, FollowedPlayerCompletionData> $followedPlayerCompletions
     * @param Collection<int, PlayerAchievementChartBucketData> $playerAchievementChartBuckets
     * @param Collection<int, GameRecentPlayerData> $recentPlayers
     * @param Collection<int, AchievementSetClaimData> $achievementSetClaims
     * @param Collection<int, CommentData> $recentVisibleComments
     * @param Collection<int, FollowedPlayerCompletionData> $followedPlayerCompletions
     * @param Collection<int, GameTopAchieverData> $topAchievers
     * @param Collection<int, PlayerAchievementChartBucketData> $playerAchievementChartBuckets
     * @param Collection<int, LeaderboardData> $featuredLeaderboards
     * @param Collection<int, LeaderboardData> $allLeaderboards
     */
    public function __construct(
        public AggregateAchievementSetCreditsData $aggregateCredits,
        public GameData $backingGame,
        public UserPermissionsData $can,
        public bool $canSubmitBetaFeedback,
        public ?GamePageClaimData $claimData,
        public GameData $game,
        public Collection $achievementSetClaims,
        public bool $hasMatureContent,
        /** @var GameSetData[] */
        public array $hubs,
        public GamePageListSort $defaultSort,
        public GamePageListSort $initialSort,
        public GamePageListView $initialView,
        public bool $isLockedOnlyFilterEnabled,
        public bool $isMissableOnlyFilterEnabled,
        public bool $isOnWantToDevList,
        public bool $isOnWantToPlayList,
        public bool $isSubscribedToComments,
        public bool $isViewingPublishedAchievements,
        public Collection $followedPlayerCompletions,
        public Collection $playerAchievementChartBuckets,
        public Lazy|Collection $featuredLeaderboards,
        #[AutoInertiaDeferred]
        public Lazy|Collection $allLeaderboards,
        public int $numBeaten,
        public int $numBeatenSoftcore,
        public int $numComments,
        public int $numCompatibleHashes,
        public int $numCompletions,
        public int $numLeaderboards,
        public int $numMasters,
        public int $numOpenTickets,
        public Collection $recentPlayers,
        public Collection $recentVisibleComments,
        /** @var GameData[] */
        public array $similarGames,
        public Collection $topAchievers,
        public ?PlayerGameData $playerGame,
        public ?PlayerGameProgressionAwardsData $playerGameProgressionAwards,
        /** @var Collection<int, PlayerAchievementSetData> */
        #[LiteralTypeScriptType('Record<number, App.Platform.Data.PlayerAchievementSet>')]
        public Collection $playerAchievementSets,
        /** @var GameAchievementSetData[] */
        public array $selectableGameAchievementSets,
        public ?SeriesHubData $seriesHub,
        public ?GameSetRequestData $setRequestData,
        public ?int $targetAchievementSetId,
    ) {
    }
}
