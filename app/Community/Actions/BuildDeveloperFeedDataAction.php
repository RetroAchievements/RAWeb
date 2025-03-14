<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\DeveloperFeedPagePropsData;
use App\Community\Data\RecentLeaderboardEntryData;
use App\Community\Data\RecentPlayerBadgeData;
use App\Community\Data\RecentUnlockData;
use App\Community\Enums\AwardType;
use App\Data\UserData;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use App\Platform\Data\LeaderboardData;
use App\Platform\Data\LeaderboardEntryData;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildDeveloperFeedDataAction
{
    public function execute(User $targetUser): DeveloperFeedPagePropsData
    {
        // Use DB::table() to avoid loading potentially thousands of Eloquent models into memory.
        $achievementInfo = DB::table('Achievements')
            ->select(['ID', 'GameID'])
            ->where('user_id', $targetUser->id)
            ->where('Flags', AchievementFlag::OfficialCore->value)
            ->get();

        $allUserAchievementIds = $achievementInfo->pluck('ID');
        $allUserGameIds = $achievementInfo->pluck('GameID')->unique();

        $activePlayers = (new BuildActivePlayersAction())->execute(gameIds: $allUserGameIds->toArray());

        $recentUnlocks = $this->getRecentUnlocks(
            $allUserAchievementIds,
            shouldUseDateRange: $targetUser->ContribCount <= 20_000,
        );

        $recentPlayerBadges = $this->getRecentPlayerBadges($allUserGameIds->toArray());

        $recentLeaderboardEntries = $this->getRecentLeaderboardEntries($targetUser);

        $props = new DeveloperFeedPagePropsData(
            activePlayers: $activePlayers,
            developer: UserData::from($targetUser),
            unlocksContributed: $targetUser->ContribCount ?? 0,
            pointsContributed: $targetUser->ContribYield ?? 0,
            awardsContributed: $this->countAwardsForGames($allUserGameIds->toArray()),
            leaderboardEntriesContributed: $this->countLeaderboardEntries($targetUser),
            recentUnlocks: $recentUnlocks,
            recentPlayerBadges: $recentPlayerBadges,
            recentLeaderboardEntries: $recentLeaderboardEntries,
        );

        return $props;
    }

    private function countAwardsForGames(array $gameIds): int
    {
        // This query counts unique mastery/beaten awards per user/game, taking only
        // the highest tier (AwardDataExtra) when multiple per user exist. Using a
        // window function instead of a self-join improves performance from 130-180ms
        // down to ~40ms.

        return DB::table(DB::raw('(
            SELECT *,
                ROW_NUMBER() OVER (
                    PARTITION BY AwardData, AwardType, user_id
                    ORDER BY AwardDataExtra DESC
                ) as rn
            FROM SiteAwards
            WHERE AwardData IN (' . implode(',', $gameIds) . ')
                AND AwardType IN (' . AwardType::Mastery . ', ' . AwardType::GameBeaten . ')
        ) ranked'))
            ->where('rn', 1)
            ->count();
    }

    private function countLeaderboardEntries(User $user): int
    {
        // We're using a JOIN instead of a subquery with IN here because MySQL can better
        // optimize the execution plan with this specific query. With a subquery, MySQL
        // will try to materialize the results first, while with a JOIN it can choose the
        // most efficient way to combine the tables. This reduces query time by ~10x.

        return DB::table('leaderboard_entries')
            ->join('LeaderboardDef', 'LeaderboardDef.ID', '=', 'leaderboard_entries.leaderboard_id')
            ->where('LeaderboardDef.author_id', $user->id)
            ->count();
    }

    /**
     * @param Collection<int, int> $achievementIds
     * @return RecentUnlockData[]
     */
    private function getRecentUnlocks(Collection $achievementIds, bool $shouldUseDateRange = false): array
    {
        $query = PlayerAchievement::with(['achievement', 'achievement.game', 'achievement.game.system', 'user'])
            ->whereIn('achievement_id', $achievementIds)
            ->orderByDesc('unlocked_at');

        if ($shouldUseDateRange) {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $query->whereDate('unlocked_at', '>=', $thirtyDaysAgo);
        }

        return $query
            ->take(200)
            ->get()
            ->reject(fn ($unlock) => $unlock->user->Untracked)
            ->map(fn ($unlock) => new RecentUnlockData(
                achievement: AchievementData::fromAchievement($unlock->achievement)->include('points'),
                game: GameData::fromGame($unlock->achievement->game)->include('badgeUrl', 'system.iconUrl', 'system.nameShort'),
                user: UserData::fromUser($unlock->user),
                unlockedAt: $unlock->unlocked_at,
                isHardcore: $unlock->unlocked_hardcore_at !== null,
            ))
            ->values()
            ->all();
    }

    /**
     * @return RecentPlayerBadgeData[]
     */
    private function getRecentPlayerBadges(array $gameIds): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        return PlayerBadge::from('SiteAwards as pb')
            ->with(['user', 'gameIfApplicable', 'gameIfApplicable.system'])
            ->whereIn('pb.AwardData', $gameIds)
            ->whereIn('pb.AwardType', [AwardType::Mastery, AwardType::GameBeaten])
            ->whereDate('pb.AwardDate', '>=', $thirtyDaysAgo)
            ->joinSub(
                PlayerBadge::selectRaw('MAX(AwardDataExtra) as MaxExtra, AwardData, AwardType, user_id')
                    ->groupBy('AwardData', 'AwardType', 'user_id'),
                'priority_awards',
                function ($join) {
                    $join->on('pb.AwardData', '=', 'priority_awards.AwardData')
                        ->on('pb.AwardType', '=', 'priority_awards.AwardType')
                        ->on('pb.user_id', '=', 'priority_awards.user_id')
                        ->on('pb.AwardDataExtra', '=', 'priority_awards.MaxExtra');
                }
            )
            ->orderByDesc('pb.AwardDate')
            ->take(50)
            ->get()
            ->reject(fn ($award) => $award->user->Untracked)
            ->map(fn ($award) => new RecentPlayerBadgeData(
                game: GameData::fromGame($award->gameIfApplicable)->include('badgeUrl', 'system.iconUrl', 'system.nameShort'),
                awardType: $award->AwardDataExtra === UnlockMode::Hardcore
                    ? ($award->AwardType === AwardType::Mastery ? 'mastered' : 'beaten-hardcore')
                    : ($award->AwardType === AwardType::Mastery ? 'completed' : 'beaten-softcore'),
                user: UserData::fromUser($award->user),
                earnedAt: $award->AwardDate,
            ))
            ->values()
            ->all();
    }

    /**
     * @return RecentLeaderboardEntryData[]
     */
    private function getRecentLeaderboardEntries(User $targetUser): array
    {
        return LeaderboardEntry::select('leaderboard_entries.*')
            ->with(['leaderboard.game.system', 'user'])
            ->join('LeaderboardDef as ld', 'ld.ID', '=', 'leaderboard_entries.leaderboard_id')
            ->where('ld.author_id', $targetUser->id)
            ->whereNull('ld.deleted_at')
            ->whereNull('leaderboard_entries.deleted_at')
            ->where('leaderboard_entries.updated_at', '>=', now()->subDays(30))
            ->orderBy('leaderboard_entries.updated_at', 'desc')
            ->take(200)
            ->get()
            ->reject(fn ($entry) => $entry->user->Untracked)
            ->map(fn ($entry) => new RecentLeaderboardEntryData(
                leaderboard: LeaderboardData::fromLeaderboard($entry->leaderboard),
                leaderboardEntry: LeaderboardEntryData::fromLeaderboardEntry($entry)->include('formattedScore'),
                game: GameData::fromGame($entry->leaderboard->game)->include('badgeUrl', 'system.iconUrl', 'system.nameShort'),
                user: UserData::fromUser($entry->user),
                submittedAt: $entry->updated_at,
            ))
            ->values()
            ->all();
    }
}
