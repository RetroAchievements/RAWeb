<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\DeveloperFeedPagePropsData;
use App\Community\Data\FeedRecentUnlockData;
use App\Community\Data\RecentLeaderboardEntryData;
use App\Community\Data\RecentPlayerBadgeData;
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
use Illuminate\Support\Facades\DB;

class BuildDeveloperFeedDataAction
{
    public function execute(User $targetUser): DeveloperFeedPagePropsData
    {
        // Use DB::table() to avoid loading potentially thousands of Eloquent models into memory.
        $authoredAchievementInfo = DB::table('achievements')
            ->select(['id', 'game_id'])
            ->where('user_id', $targetUser->id)
            ->where('is_promoted', true)
            ->get();

        $maintainedAchievementInfo = DB::table('achievement_maintainers')
            ->join('achievements', 'achievements.id', '=', 'achievement_maintainers.achievement_id')
            ->select(['achievements.id', 'achievements.game_id'])
            ->where('achievement_maintainers.user_id', $targetUser->id)
            ->where('achievement_maintainers.is_active', true)
            ->where('achievements.user_id', '!=', $targetUser->id)
            ->where('achievements.is_promoted', true)
            ->get();

        $allAchievementIds = $authoredAchievementInfo->pluck('id')
            ->merge($maintainedAchievementInfo->pluck('id'))
            ->unique();
        $allGameIds = $authoredAchievementInfo->pluck('game_id')
            ->merge($maintainedAchievementInfo->pluck('game_id'))
            ->unique();

        $authoredGameIds = $authoredAchievementInfo->pluck('game_id')->unique();

        $activePlayers = (new BuildActivePlayersAction())->execute(gameIds: $allGameIds->toArray());

        $recentUnlocks = $this->getRecentUnlocks(
            $allAchievementIds,
            shouldUseDateRange: $targetUser->yield_unlocks <= 20_000,
            allowPartialResults: $targetUser->yield_unlocks >= 1_000_000,
        );

        $recentPlayerBadges = $this->getRecentPlayerBadges($authoredGameIds->toArray());

        $recentLeaderboardEntries = $this->getRecentLeaderboardEntries($targetUser);

        $props = new DeveloperFeedPagePropsData(
            activePlayers: $activePlayers,
            developer: UserData::from($targetUser),
            unlocksContributed: $targetUser->yield_unlocks ?? 0,
            pointsContributed: $targetUser->yield_points ?? 0,
            awardsContributed: $this->countAwardsForGames($authoredGameIds->toArray()),
            leaderboardEntriesContributed: $this->countLeaderboardEntries($targetUser),
            recentUnlocks: $recentUnlocks,
            recentPlayerBadges: $recentPlayerBadges,
            recentLeaderboardEntries: $recentLeaderboardEntries,
        );

        return $props;
    }

    private function countAwardsForGames(array $gameIds): int
    {
        if (empty($gameIds)) {
            return 0;
        }

        $awards = DB::table('user_awards')
            ->select(['award_key', 'award_type', 'user_id'])
            ->whereIn('award_key', $gameIds)
            ->whereIn('award_type', [AwardType::Mastery->value, AwardType::GameBeaten->value])
            ->distinct();

        return DB::query()->fromSub($awards, 'awards')->count();
    }

    private function countLeaderboardEntries(User $user): int
    {
        // We're using a JOIN instead of a subquery with IN here because MySQL can better
        // optimize the execution plan with this specific query. With a subquery, MySQL
        // will try to materialize the results first, while with a JOIN it can choose the
        // most efficient way to combine the tables. This reduces query time by ~10x.
        return DB::table('leaderboard_entries')
            ->join('leaderboards', 'leaderboards.id', '=', 'leaderboard_entries.leaderboard_id')
            ->where('leaderboards.author_id', $user->id)
            ->count();
    }

    /**
     * @param Collection<int, int> $achievementIds
     * @return FeedRecentUnlockData[]
     */
    private function getRecentUnlocks(
        Collection $achievementIds,
        bool $shouldUseDateRange = false,
        bool $allowPartialResults = false,
    ): array {
        $query = PlayerAchievement::query()
            ->whereIn('achievement_id', $achievementIds)
            ->orderByDesc('unlocked_at')
            ->orderByDesc('id')
            ->take(200);

        if ($shouldUseDateRange) {
            $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();
            $query->where('unlocked_at', '>=', $thirtyDaysAgo);
        }

        $unlocks = null;
        if (!$shouldUseDateRange && $achievementIds->isNotEmpty()) {
            foreach ($allowPartialResults ? [100_000, 1_000_000] : [100_000] as $scanLimit) {
                $cutoffUnlock = DB::table('player_achievements')
                    ->select(['id', 'unlocked_at'])
                    ->orderByDesc('unlocked_at')
                    ->orderByDesc('id')
                    ->offset($scanLimit - 1)
                    ->first();

                $recentQuery = (clone $query)->forceIndex('player_achievements_unlocked_at_index');
                if ($cutoffUnlock !== null) {
                    $recentQuery->where(function ($query) use ($cutoffUnlock) {
                        if ($cutoffUnlock->unlocked_at === null) {
                            $query->whereNotNull('unlocked_at');
                        } else {
                            $query->where('unlocked_at', '>', $cutoffUnlock->unlocked_at);
                        }

                        $query->orWhere(fn ($query) => $query
                            ->where('unlocked_at', $cutoffUnlock->unlocked_at)
                            ->where('id', '>=', $cutoffUnlock->id));
                    });
                }

                $unlocks = $recentQuery->get();
                if ($unlocks->count() === 200 || $cutoffUnlock === null) {
                    break;
                }
            }
        }

        if ($unlocks === null || (!$allowPartialResults && $unlocks->count() < 200)) {
            $unlocks = $query->get();
        }

        return $unlocks
            ->load(['achievement', 'achievement.game', 'achievement.game.system', 'user'])
            ->reject(fn ($unlock) => $unlock->user === null || $unlock->user->unranked_at !== null)
            ->map(fn ($unlock) => new FeedRecentUnlockData(
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
        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();

        return PlayerBadge::from('user_awards as pb')
            ->with(['user', 'gameIfApplicable', 'gameIfApplicable.system'])
            ->whereIn('pb.award_key', $gameIds)
            ->whereIn('pb.award_type', [AwardType::Mastery, AwardType::GameBeaten])
            ->where(DB::raw('pb.awarded_at'), '>=', $thirtyDaysAgo)
            ->joinSub(
                PlayerBadge::selectRaw('MAX(award_tier) as MaxExtra, award_key, award_type, user_id')
                    ->groupBy('award_key', 'award_type', 'user_id'),
                'priority_awards',
                function ($join) {
                    $join->on('pb.award_key', '=', 'priority_awards.award_key')
                        ->on('pb.award_type', '=', 'priority_awards.award_type')
                        ->on('pb.user_id', '=', 'priority_awards.user_id')
                        ->on('pb.award_tier', '=', 'priority_awards.MaxExtra');
                }
            )
            ->orderByDesc('pb.awarded_at')
            ->take(50)
            ->get()
            ->reject(fn ($award) => $award->user === null || $award->user->unranked_at !== null)
            ->map(fn ($award) => new RecentPlayerBadgeData(
                game: GameData::fromGame($award->gameIfApplicable)->include('badgeUrl', 'system.iconUrl', 'system.nameShort'),
                awardType: $award->award_tier === UnlockMode::Hardcore
                    ? ($award->award_type === AwardType::Mastery ? 'mastered' : 'beaten-hardcore')
                    : ($award->award_type === AwardType::Mastery ? 'completed' : 'beaten-casual'),
                user: UserData::fromUser($award->user),
                earnedAt: $award->awarded_at,
            ))
            ->values()
            ->all();
    }

    /**
     * @return RecentLeaderboardEntryData[]
     */
    private function getRecentLeaderboardEntries(User $targetUser): array
    {
        $leaderboardIds = DB::table('leaderboards')
            ->where('author_id', $targetUser->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        $recentEntries = LeaderboardEntry::select('id')
            ->whereIn('leaderboard_id', $leaderboardIds)
            ->where('updated_at', '>=', now()->subDays(30))
            ->orderByDesc('updated_at')
            ->take(200);

        return LeaderboardEntry::select('leaderboard_entries.*')
            ->with(['leaderboard.game.system', 'user'])
            ->joinSub($recentEntries, 'recent_entries', 'recent_entries.id', '=', 'leaderboard_entries.id')
            ->orderByDesc('leaderboard_entries.updated_at')
            ->get()
            ->reject(fn ($entry) => $entry->user === null || $entry->user->unranked_at !== null)
            ->map(fn ($entry) => new RecentLeaderboardEntryData(
                leaderboard: LeaderboardData::fromLeaderboard($entry->leaderboard),
                leaderboardEntry: LeaderboardEntryData::fromLeaderboardEntry($entry, $entry->leaderboard->format)->include('formattedScore'),
                game: GameData::fromGame($entry->leaderboard->game)->include('badgeUrl', 'system.iconUrl', 'system.nameShort'),
                user: UserData::fromUser($entry->user),
                submittedAt: $entry->updated_at,
            ))
            ->values()
            ->all();
    }
}
