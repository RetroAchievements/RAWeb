<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\User;
use App\Platform\Data\PlayerAchievementChartBucketData;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use Illuminate\Support\Collection;

class BuildGameAchievementDistributionAction
{
    /**
     * Generate achievement distribution chart data for a game.
     *
     * @return Collection<int, PlayerAchievementChartBucketData>
     */
    public function execute(Game $game, ?User $user): Collection
    {
        $numDistinctPlayers = $game->players_total;
        $numAchievements = $game->achievements_published;

        $softcoreUnlocks = getAchievementDistribution(
            $game->id,
            UnlockMode::Softcore,
            $user?->username,
            AchievementFlag::OfficialCore,
            $numDistinctPlayers
        );
        $hardcoreUnlocks = getAchievementDistribution(
            $game->id,
            UnlockMode::Hardcore,
            $user?->username,
            AchievementFlag::OfficialCore,
            $numDistinctPlayers
        );

        [$buckets, $isDynamicBucketingEnabled] = generateEmptyBucketsWithBounds($numAchievements);
        calculateBuckets($buckets, $isDynamicBucketingEnabled, $numAchievements, $softcoreUnlocks, $hardcoreUnlocks);
        handleAllAchievementsCase($numAchievements, $softcoreUnlocks, $hardcoreUnlocks, $buckets);

        $previousEnd = 0;

        /** @var array<int, array{start?: int, end?: int, hardcore: int, softcore: int}> $buckets */
        return collect($buckets)->map(function (array $bucket) use (&$previousEnd): PlayerAchievementChartBucketData {
            // Handle undefined start and end based on the previous bucket's end value.
            if (!isset($bucket['start']) || !isset($bucket['end'])) {
                $bucket['start'] = $previousEnd + 1;
                $bucket['end'] = $previousEnd + 1;
            }

            // Store the current end value for the next iteration.
            $previousEnd = $bucket['end'];

            return PlayerAchievementChartBucketData::from($bucket);
        });
    }
}
