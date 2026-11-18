<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Models\PlayerGlobalRanking;
use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingSortField;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GetGlobalRankingDataAction
{
    /**
     * Gets global ranking data from the player_global_rankings materialized view.
     * Always call this outside an existing database transaction.
     *
     * @return array<int, array{
     *     userId: int,
     *     username: string,
     *     displayName: string|null,
     *     achievementsUnlocked: int,
     *     points: int,
     *     weightedPoints: int,
     *     retroRatio: float,
     *     awardsCount: int,
     *     rankNumber: int|null,
     * }>
     */
    public function execute(
        GlobalRankingWindow $window,
        GlobalRankingMode $mode,
        GlobalRankingSortField $sortBy = GlobalRankingSortField::Points,
        bool $isDescending = true,
        ?User $user = null,
        ?User $followedByUser = null,
        int $offset = 0,
        int $limit = 50,
    ): array {
        $query = PlayerGlobalRanking::query()
            ->select('player_global_rankings.*')
            ->addSelect([
                'username' => 'users.username',
                'display_name' => 'users.display_name',
            ])
            ->join('users', 'users.id', '=', 'player_global_rankings.user_id')
            ->whereNull('users.deleted_at')
            ->where('player_global_rankings.window', $window)
            ->where('player_global_rankings.mode', $mode);

        if ($window === GlobalRankingWindow::AllTime && $followedByUser === null) {
            $rankColumn = $sortBy->rankColumn() ?? 'rank_number';
            $query->whereNotNull("player_global_rankings.{$rankColumn}");
        }

        if ($user !== null) {
            $query->where('player_global_rankings.user_id', $user->id);
        }

        if ($followedByUser !== null) {
            $followedUserIds = $followedByUser->followedUsers()
                ->whereNull('users.banned_at')
                ->pluck('users.id')
                ->push($followedByUser->id);
            $query->whereIn('player_global_rankings.user_id', $followedUserIds);
        }

        $query->orderBy("player_global_rankings.{$sortBy->value}", $isDescending ? 'desc' : 'asc')
            ->orderBy('users.username');

        $rankings = $user === null && $followedByUser === null
            ? $this->getPage($query, $sortBy, $isDescending, $offset, $limit)
            : $query->offset($offset)->limit($limit)->get();

        $awardsByUserId = $window === GlobalRankingWindow::AllTime
            ? $this->getAwardsByUserId($rankings->pluck('user_id')->all(), $mode)
            : [];

        return $rankings->map(fn (PlayerGlobalRanking $ranking): array => $this->formatRanking(
            ranking: $ranking,
            mode: $mode,
            sortBy: $sortBy,
            awardsCount: $awardsByUserId[$ranking->user_id] ?? $ranking->awards_count,
            showRank: $followedByUser === null,
        ))->all();
    }

    /**
     * @return array{
     *     userId: int,
     *     username: string,
     *     displayName: string|null,
     *     achievementsUnlocked: int,
     *     points: int,
     *     weightedPoints: int,
     *     retroRatio: float,
     *     awardsCount: int,
     *     rankNumber: int|null,
     * }
     */
    private function formatRanking(
        PlayerGlobalRanking $ranking,
        GlobalRankingMode $mode,
        GlobalRankingSortField $sortBy,
        int $awardsCount,
        bool $showRank,
    ): array {
        $weightedPoints = $mode === GlobalRankingMode::Hardcore ? $ranking->points_weighted : 0;

        $rankColumn = $sortBy->rankColumn();
        $rankNumber = $showRank && $rankColumn !== null ? $ranking->getAttribute($rankColumn) : null;

        return [
            'userId' => $ranking->user_id,
            'username' => $ranking->getAttribute('username'),
            'displayName' => $ranking->getAttribute('display_name'),
            'achievementsUnlocked' => $ranking->achievements_unlocked,
            'points' => $ranking->points,
            'weightedPoints' => $weightedPoints,
            'retroRatio' => $ranking->points === 0 ? 0.0 : round($weightedPoints / $ranking->points, 2),
            'awardsCount' => $awardsCount,
            'rankNumber' => $rankNumber,
        ];
    }

    /**
     * @param Builder<PlayerGlobalRanking> $query
     * @return Collection<int, PlayerGlobalRanking>
     */
    private function getPage(
        Builder $query,
        GlobalRankingSortField $sortBy,
        bool $isDescending,
        int $offset,
        int $limit,
    ): Collection {
        /**
         * We're using multiple queries to do a read, and a rebuild can technically
         * commit between our queries. This will lead to unexpected behavior, so
         * we'll read in a transaction to mitigate this.
         */
        return DB::transaction(function () use ($query, $sortBy, $isDescending, $offset, $limit): Collection {
            $sortColumn = "player_global_rankings.{$sortBy->value}";
            $pageValues = (clone $query)
                ->select($sortColumn)
                ->reorder($sortColumn, $isDescending ? 'desc' : 'asc')
                ->offset($offset)
                ->limit($limit)
                ->toBase()
                ->pluck($sortBy->value);

            if ($pageValues->isEmpty()) {
                return new Collection();
            }

            // Keep every tie at the page boundary before sorting by username.
            $boundaryValue = $pageValues->last();

            return $query
                ->where($sortColumn, $isDescending ? '>=' : '<=', $boundaryValue)
                ->offset($offset)
                ->limit($limit)
                ->get();
        });
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, int>
     */
    private function getAwardsByUserId(array $userIds, GlobalRankingMode $mode): array
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
}
