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
use App\Platform\Enums\UnlockMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildDeveloperFeedDataAction
{
    public function execute(User $targetUser): DeveloperFeedPagePropsData
    {
        $allUserAchievements = collect(getUserAchievementInformation($targetUser));
        $allUserAchievementIds = $allUserAchievements->pluck('ID');
        $allUserGameIds = $allUserAchievements->pluck('GameID')->unique();

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
        return PlayerBadge::from('SiteAwards as pb')
            ->whereIn('pb.AwardData', $gameIds)
            ->whereIn('pb.AwardType', [AwardType::Mastery, AwardType::GameBeaten])
            ->joinSub(
                // If a user has both softcore and hardcore awards, only count the most prestigious.
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
            ->count();
    }

    private function countLeaderboardEntries(User $user): int
    {
        return LeaderboardEntry::query()
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
                achievement: AchievementData::fromAchievement($unlock->achievement)->include('badgeUnlockedUrl', 'points'),
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
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        return LeaderboardEntry::query()
            ->with(['leaderboard', 'leaderboard.game', 'leaderboard.game.system', 'user'])
            ->join('LeaderboardDef', 'LeaderboardDef.ID', '=', 'leaderboard_entries.leaderboard_id')
            ->where('LeaderboardDef.author_id', $targetUser->id)
            ->whereDate('leaderboard_entries.updated_at', '>=', $thirtyDaysAgo)
            ->orderByDesc('leaderboard_entries.updated_at')
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
