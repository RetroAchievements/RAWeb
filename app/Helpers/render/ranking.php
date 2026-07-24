<?php

declare(strict_types=1);

use App\Community\Enums\AwardType;
use App\Enums\Permissions;
use App\Models\PlayerBadge;
use App\Models\PlayerGlobalRanking;
use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;

/**
 * Gets global ranking data from the materialized rankings store.
 *
 * @param int $lbType 0 daily, 1 weekly, 2 all-time
 * @param int $sort legacy ranking sort identifier
 * @param string $date retained for legacy callers; current windows always use UTC
 * @param string|null $user a single user to return
 * @param string|null $friendsOf a user whose followed users constrain the board
 * @param int $untracked retained for legacy callers; untracked users have no store rows
 * @param int $offset starting row offset
 * @param int $count maximum rows to return
 * @param int $info retained for legacy callers
 * @return array<int, array<string, int|float|string|null>>
 */
function getGlobalRankingData(
    int $lbType,
    int $sort,
    string $date,
    ?string $user,
    ?string $friendsOf = null,
    int $untracked = 0,
    int $offset = 0,
    int $count = 50,
    int $info = 0,
): array {
    if ($untracked === 1) {
        return [];
    }

    $window = match ($lbType) {
        0 => GlobalRankingWindow::Daily,
        1 => GlobalRankingWindow::Weekly,
        2 => GlobalRankingWindow::AllTime,
        default => GlobalRankingWindow::Daily,
    };
    [$sort, $descending] = normalizeGlobalRankingSort($sort, $window);
    $mode = in_array($sort, [2, 3, 8], true)
        ? GlobalRankingMode::Casual
        : GlobalRankingMode::Hardcore;
    $sortColumn = match ($sort) {
        3, 4 => 'achievements_unlocked',
        6 => 'points_weighted',
        8, 9 => 'awards_count',
        default => 'points',
    };

    $query = PlayerGlobalRanking::query()
        ->select('player_global_rankings.*')
        ->addSelect([
            'username' => 'users.username',
            'display_name' => 'users.display_name',
            'deleted_at' => 'users.deleted_at',
        ])
        ->join('users', 'users.id', '=', 'player_global_rankings.user_id')
        ->whereNull('users.deleted_at')
        ->where('player_global_rankings.window', $window)
        ->where('player_global_rankings.mode', $mode);

    if ($window === GlobalRankingWindow::AllTime && $friendsOf === null) {
        $query->whereNotNull('player_global_rankings.' . ($sort === 6 ? 'weighted_rank_number' : 'rank_number'));
    }

    if ($user !== null) {
        $query->where(function ($query) use ($user): void {
            $query->where('users.username', $user)
                ->orWhere('users.display_name', $user);
        });
    }

    if ($friendsOf !== null) {
        $friend = User::whereName($friendsOf)->first();
        if ($friend === null) {
            return [];
        }

        $followedUserIds = $friend->followedUsers()
            ->where('Permissions', '>=', Permissions::Unregistered)
            ->pluck('users.id')
            ->push($friend->id);
        $query->whereIn('player_global_rankings.user_id', $followedUserIds);
    }

    $query->orderBy("player_global_rankings.{$sortColumn}", $descending ? 'desc' : 'asc')
        ->orderBy('users.username');

    $rankings = $query
        ->offset($offset)
        ->limit($count)
        ->get();

    $awardsByUserId = $window === GlobalRankingWindow::AllTime
        ? globalRankingAwardsByUserId($rankings->pluck('user_id')->all(), $mode)
        : [];

    return $rankings->map(function (PlayerGlobalRanking $ranking) use ($mode, $sort, $awardsByUserId, $friendsOf): array {
        $retroPoints = $mode === GlobalRankingMode::Hardcore ? $ranking->points_weighted : 0;

        $rankNumber = $friendsOf !== null ? null : match ($sort) {
            2, 5 => $ranking->rank_number,
            6 => $ranking->weighted_rank_number,
            default => null,
        };

        return [
            'ID' => $ranking->user_id,
            'User' => $ranking->getAttribute('username'),
            'DisplayName' => $ranking->getAttribute('display_name'),
            'DeletedAt' => $ranking->getAttribute('deleted_at'),
            'AchievementCount' => $ranking->achievements_unlocked,
            'Points' => $ranking->points,
            'RetroPoints' => $retroPoints,
            'RetroRatio' => $ranking->points === 0 ? 0 : round($retroPoints / $ranking->points, 2),
            'TotalAwards' => $awardsByUserId[$ranking->user_id] ?? $ranking->awards_count,
            'RankNumber' => $rankNumber,
        ];
    })->all();
}

/**
 * @return array{int, bool}
 */
function normalizeGlobalRankingSort(int $sort, GlobalRankingWindow $window): array
{
    $descending = $sort < 10;
    $sort = $descending ? $sort : $sort - 10;

    if ($sort === 7 || ($window === GlobalRankingWindow::AllTime && in_array($sort, [8, 9], true))) {
        return [5, true];
    }

    return [$sort, $descending];
}

/**
 * @param array<int, int> $userIds
 * @return array<int, int>
 */
function globalRankingAwardsByUserId(array $userIds, GlobalRankingMode $mode): array
{
    if ($userIds === []) {
        return [];
    }

    $awardCount = $mode === GlobalRankingMode::Hardcore
        ? 'COALESCE(SUM(CASE WHEN award_tier > 0 THEN 1 ELSE 0 END), 0)'
        : 'COUNT(*)';

    return PlayerBadge::query()
        ->select('user_id')
        ->selectRaw("{$awardCount} AS awards_count")
        ->whereIn('user_id', $userIds)
        ->where('award_type', AwardType::Mastery->value)
        ->groupBy('user_id')
        ->pluck('awards_count', 'user_id')
        ->map(fn ($count): int => (int) $count)
        ->all();
}
