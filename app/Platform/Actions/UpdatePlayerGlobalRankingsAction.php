<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerGlobalRanking;
use App\Models\PlayerGlobalRankingTotal;
use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePlayerGlobalRankingsAction
{
    public function execute(GlobalRankingWindow $window): void
    {
        // Compute window boundaries once so every mode in this rebuild shares the
        // same period, even if the wall clock crosses a day or week boundary mid-run.
        $boundaries = $this->windowBoundaries($window);

        try {
            Cache::lock('player-global-rankings-update', 300)->block(240, function () use ($window, $boundaries): void {
                DB::transaction(function () use ($window, $boundaries): void {
                    PlayerGlobalRanking::query()->where('window', $window)->delete();

                    foreach (GlobalRankingMode::cases() as $mode) {
                        PlayerGlobalRanking::insertUsing(
                            [
                                'user_id',
                                'window',
                                'mode',
                                'achievements_unlocked',
                                'points',
                                'points_weighted',
                                'awards_count',
                                'rank_number',
                                'weighted_rank_number',
                                'created_at',
                            ],
                            $this->rankingSelect($window, $mode, $boundaries),
                        );
                    }

                    if ($window === GlobalRankingWindow::AllTime) {
                        $this->replaceRankedUserTotals();
                    }
                });
            });
        } catch (LockTimeoutException) {
            Log::warning('Skipped player global rankings rebuild: lock timed out.', [
                'window' => $window->value,
            ]);
        }
    }

    /**
     * @return array{Carbon, Carbon}|null
     */
    private function windowBoundaries(GlobalRankingWindow $window): ?array
    {
        if ($window === GlobalRankingWindow::AllTime) {
            return null;
        }

        $startsAt = $window === GlobalRankingWindow::Daily
            ? Carbon::now('UTC')->startOfDay()
            : Carbon::now('UTC')->startOfWeek(Carbon::SUNDAY);
        $endsAt = $window === GlobalRankingWindow::Daily
            ? $startsAt->copy()->addDay()
            : $startsAt->copy()->addWeek();

        return [$startsAt, $endsAt];
    }

    /**
     * @param array{Carbon, Carbon}|null $boundaries
     */
    private function rankingSelect(GlobalRankingWindow $window, GlobalRankingMode $mode, ?array $boundaries): Builder
    {
        $aggregates = $boundaries === null
            ? $this->allTimeAggregate($mode)
            : $this->windowAggregate($mode, $boundaries[0], $boundaries[1]);

        $weightedRank = $mode === GlobalRankingMode::Hardcore
            ? 'CASE WHEN aggregates.points_weighted >= ? THEN RANK() OVER (ORDER BY CASE WHEN aggregates.points_weighted >= ? THEN aggregates.points_weighted END DESC) END'
            : 'NULL';
        $weightedBindings = $mode === GlobalRankingMode::Hardcore
            ? [Rank::MIN_TRUE_POINTS, Rank::MIN_TRUE_POINTS]
            : [];

        return DB::query()
            ->fromSub($aggregates, 'aggregates')
            ->selectRaw(
                "aggregates.user_id,
                ? AS `window`,
                ? AS mode,
                aggregates.achievements_unlocked,
                aggregates.points,
                aggregates.points_weighted,
                aggregates.awards_count,
                CASE WHEN aggregates.points >= ? THEN RANK() OVER (ORDER BY CASE WHEN aggregates.points >= ? THEN aggregates.points END DESC) END AS rank_number,
                {$weightedRank} AS weighted_rank_number,
                CURRENT_TIMESTAMP AS created_at",
                [
                    $window->value,
                    $mode->value,
                    Rank::MIN_POINTS,
                    Rank::MIN_POINTS,
                    ...$weightedBindings,
                ],
            );
    }

    private function allTimeAggregate(GlobalRankingMode $mode): Builder
    {
        $isHardcore = $mode === GlobalRankingMode::Hardcore;
        $pointsColumn = $isHardcore ? 'points_hardcore' : 'points';
        $weightedPoints = $isHardcore ? 'COALESCE(users.points_weighted, 0)' : '0';

        // clamped at zero because a handful of users in prod have achievements_unlocked below
        // achievements_unlocked_hardcore, and a negative value could abort the whole rebuild
        $unlockedAchievements = $isHardcore
            ? 'COALESCE(users.achievements_unlocked_hardcore, 0)'
            : 'CASE WHEN COALESCE(users.achievements_unlocked - users.achievements_unlocked_hardcore, 0) > 0 THEN users.achievements_unlocked - users.achievements_unlocked_hardcore ELSE 0 END';

        return User::query()
            ->selectRaw(
                "users.id AS user_id,
                {$unlockedAchievements} AS achievements_unlocked,
                COALESCE(users.{$pointsColumn}, 0) AS points,
                {$weightedPoints} AS points_weighted,
                0 AS awards_count",
            )
            ->whereNull('users.unranked_at')
            /**
             * Materialize everyone with any points at all so friends lists can include
             * sub-threshold players. The rank columns stay NULL below the minimums, and
             * the public leaderboard does a filter, so global rankings remain gated by
             * our min rank threshold.
             */
            ->where(function ($query) use ($isHardcore, $pointsColumn): void {
                $query->where("users.{$pointsColumn}", '>', 0);

                if ($isHardcore) {
                    $query->orWhere('users.points_weighted', '>', 0);
                }
            })
            ->toBase();
    }

    private function windowAggregate(GlobalRankingMode $mode, Carbon $startsAt, Carbon $endsAt): Builder
    {
        $timestampColumn = $mode === GlobalRankingMode::Hardcore ? 'unlocked_hardcore_at' : 'unlocked_at';
        $weightedPoints = $mode === GlobalRankingMode::Hardcore
            ? 'SUM(achievements.points_weighted)'
            : '0';
        $awardCount = $mode === GlobalRankingMode::Hardcore
            ? 'SUM(CASE WHEN awards.award_tier > 0 THEN 1 ELSE 0 END)'
            : 'COUNT(awards.id)';

        $achievements = PlayerAchievement::query()
            ->from('player_achievements as player_achievements')
            ->selectRaw("player_achievements.user_id, COUNT(*) AS achievements_unlocked, SUM(achievements.points) AS points, {$weightedPoints} AS points_weighted")
            ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
            ->join('users', 'users.id', '=', 'player_achievements.user_id')
            ->whereNull('users.unranked_at')
            ->whereNull('users.deleted_at')
            ->where('player_achievements.' . $timestampColumn, '>=', $startsAt)
            ->where('player_achievements.' . $timestampColumn, '<', $endsAt)
            ->groupBy('player_achievements.user_id');

        $awards = PlayerBadge::query()
            ->from('user_awards as awards')
            ->selectRaw("awards.user_id, {$awardCount} AS awards_count")
            ->join('users', 'users.id', '=', 'awards.user_id')
            ->whereNull('users.unranked_at')
            ->whereNull('users.deleted_at')
            ->whereRaw('awards.award_type = ?', [AwardType::Mastery->value])
            ->whereRaw('awards.awarded_at >= ?', [$startsAt])
            ->whereRaw('awards.awarded_at < ?', [$endsAt])
            ->groupBy('awards.user_id');

        return DB::query()
            ->fromSub($achievements, 'achievement_totals')
            ->leftJoinSub($awards, 'award_totals', 'award_totals.user_id', '=', 'achievement_totals.user_id')
            ->selectRaw(
                'achievement_totals.user_id,
                achievement_totals.achievements_unlocked,
                achievement_totals.points,
                achievement_totals.points_weighted,
                COALESCE(award_totals.awards_count, 0) AS awards_count',
            )
            ->where('achievement_totals.points', '>', 0);
    }

    private function replaceRankedUserTotals(): void
    {
        $rankings = PlayerGlobalRanking::query()->where('window', GlobalRankingWindow::AllTime);

        PlayerGlobalRankingTotal::query()->delete();
        PlayerGlobalRankingTotal::insert([
            [
                'rank_type' => RankType::Hardcore,
                'total' => (clone $rankings)
                    ->where('mode', GlobalRankingMode::Hardcore)
                    ->whereNotNull('rank_number')
                    ->count(),
                'created_at' => now(),
            ],
            [
                'rank_type' => RankType::Casual,
                'total' => (clone $rankings)
                    ->where('mode', GlobalRankingMode::Casual)
                    ->whereNotNull('rank_number')
                    ->count(),
                'created_at' => now(),
            ],
            [
                'rank_type' => RankType::TruePoints,
                'total' => (clone $rankings)
                    ->where('mode', GlobalRankingMode::Hardcore)
                    ->whereNotNull('weighted_rank_number')
                    ->count(),
                'created_at' => now(),
            ],
        ]);
    }
}
