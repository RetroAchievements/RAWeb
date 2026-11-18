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
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePlayerGlobalRankingsAction
{
    /**
     * We use a staging table to compute player rankings before updating the real table.
     * The staging table enables us to use a differential sync instead of deleting and
     * then recreating the entire window. This significantly reduces DB write volume,
     * lock contention, index churn, etc. It also allows us to preserve unchanged rows.
     */
    private const TEMPORARY_TABLE = 'temp_player_global_rankings';

    /**
     * Metric and ranking columns compared during reconciliation.
     * An existing player row updates when any of these values change.
     */
    private const STAT_COLUMNS = ['achievements_unlocked', 'points', 'points_weighted', 'awards_count', 'rank_number', 'weighted_rank_number'];

    /**
     * The complete column list that's used when we insert staged rows and append newly qualified players.
     */
    private const INSERT_COLUMNS = ['user_id', 'window', 'mode', ...self::STAT_COLUMNS, 'created_at'];

    public function execute(GlobalRankingWindow $window): void
    {
        // Compute window boundaries once so every mode in this rebuild shares the
        // same period, even if the wall clock crosses a day or week boundary mid-run.
        $boundaries = $this->windowBoundaries($window);

        // This is a DDL statement, and in MariaDB, DDL statements change the transaction
        // isolation level. Because we need to manually set the transaction isolation level
        // ourselves in the `rebuild()` method, we need to instantiate the temp table here.
        Schema::create(self::TEMPORARY_TABLE, function (Blueprint $table): void {
            $table->temporary(); // !!
            $table->unsignedBigInteger('user_id');
            $table->string('window', 10);
            $table->string('mode', 10);
            $table->bigInteger('achievements_unlocked');
            $table->bigInteger('points');
            $table->bigInteger('points_weighted');
            $table->bigInteger('awards_count');
            $table->bigInteger('rank_number')->nullable();
            $table->bigInteger('weighted_rank_number')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->primary(['mode', 'user_id']);
        });

        try {
            $this->rebuild($window, $boundaries);
        } finally {
            /**
             * No matter what happens inside `rebuild()`, we _ALWAYS_ have to clean
             * up the temp table. If we don't purge it as soon as we're done, ranking
             * resyncs will be frozen until Horizon restarts.
             */

            // SQLite doesn't support the TEMPORARY keyword in a drop statement.
            DB::statement(DB::connection()->getDriverName() === 'sqlite'
                ? 'DROP TABLE ' . self::TEMPORARY_TABLE
                : 'DROP TEMPORARY TABLE ' . self::TEMPORARY_TABLE);
        }
    }

    /**
     * @param array{Carbon, Carbon}|null $boundaries
     */
    private function rebuild(GlobalRankingWindow $window, ?array $boundaries): void
    {
        /**
         * The default REPEATABLE READ isolation makes INSERT ... SELECT hold
         * shared locks on each source row it reads. These locks block unlock
         * inserts and achievement metric updates until the rebuild ends.
         * READ COMMITTED makes the same reads lock-free.
         *
         * This only affects the next transaction on the current DB connection.
         */
        if (DB::transactionLevel() === 0) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        }

        DB::transaction(function () use ($window, $boundaries): void {
            // Insert all the computed rankings into the temp table.
            foreach (GlobalRankingMode::cases() as $mode) {
                DB::table(self::TEMPORARY_TABLE)->insertUsing(self::INSERT_COLUMNS, $this->rankingSelect($window, $mode, $boundaries));
            }

            $this->reconcileRankingsWithTempTable($window);

            if ($window === GlobalRankingWindow::AllTime) {
                $this->replaceRankedUserTotals();
            }
        });
    }

    private function reconcileRankingsWithTempTable(GlobalRankingWindow $window): void
    {
        $matchingRecordFilter = fn (Builder $subquery): Builder => $subquery
            ->from(self::TEMPORARY_TABLE . ' as staging')
            ->whereColumn('staging.mode', 'player_global_rankings.mode')
            ->whereColumn('staging.user_id', 'player_global_rankings.user_id');

        // We'll ensure only changed rows actually get updated in the real table,
        // which dramatically reduces DB workload and potential contention issues.
        $identicalDataFilter = function (Builder $subquery) use ($matchingRecordFilter): void {
            $matchingRecordFilter($subquery);
            foreach (self::STAT_COLUMNS as $field) {
                $subquery->whereRaw(
                    "(staging.{$field} = player_global_rankings.{$field} OR (staging.{$field} IS NULL AND player_global_rankings.{$field} IS NULL))"
                );
            }
        };

        PlayerGlobalRanking::query()->where('window', $window)->whereNotExists($matchingRecordFilter)->delete();

        // Subqueries populate each column because SQLite forbids UPDATE from joined tables.
        $columnsToSync = [...self::STAT_COLUMNS, 'created_at'];
        $fieldUpdates = [];
        foreach ($columnsToSync as $col) {
            $fieldUpdates[$col] = DB::raw("(SELECT staging.{$col} FROM " . self::TEMPORARY_TABLE . ' AS staging WHERE staging.mode = player_global_rankings.mode AND staging.user_id = player_global_rankings.user_id)');
        }

        PlayerGlobalRanking::query()->where('window', $window)->whereNotExists($identicalDataFilter)->update($fieldUpdates);

        PlayerGlobalRanking::insertUsing(self::INSERT_COLUMNS, DB::table(self::TEMPORARY_TABLE . ' as staging')
            ->select(self::INSERT_COLUMNS)
            ->whereNotExists(fn (Builder $subquery): Builder => $subquery
                ->from('player_global_rankings as current')
                ->where('current.window', $window->value)
                ->whereColumn('current.mode', 'staging.mode')
                ->whereColumn('current.user_id', 'staging.user_id')));
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

        $unlockedAchievements = $isHardcore
            ? 'COALESCE(users.achievements_unlocked_hardcore, 0)'
            : 'CASE WHEN users.achievements_unlocked > users.achievements_unlocked_hardcore THEN users.achievements_unlocked - users.achievements_unlocked_hardcore ELSE 0 END';

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
        [$timestampColumn, $forcedIndex] = $mode === GlobalRankingMode::Hardcore
            ? ['unlocked_hardcore_at', 'player_achievements_unlocked_hardcore_at_index']
            : ['unlocked_at', 'player_achievements_unlocked_at_index'];
        $weightedPoints = $mode === GlobalRankingMode::Hardcore
            ? 'SUM(achievements.points_weighted)'
            : '0';
        $awardCount = $mode === GlobalRankingMode::Hardcore
            ? 'SUM(CASE WHEN awards.award_tier > 0 THEN 1 ELSE 0 END)'
            : 'COUNT(awards.id)';

        $achievements = PlayerAchievement::query()
            ->from('player_achievements as player_achievements')
            ->forceIndex($forcedIndex) // otherwise, the query planner scans all users sequentially to avoid sorting the GROUP BY
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
        PlayerGlobalRankingTotal::query()->delete();
        PlayerGlobalRankingTotal::insert(
            array_map(
                fn (RankType $rankType): array => [
                    'rank_type' => $rankType,
                    'total' => PlayerGlobalRanking::query()
                        ->where('window', GlobalRankingWindow::AllTime)
                        ->where('mode', $rankType->mode())
                        ->whereNotNull($rankType->rankColumn())
                        ->count(),
                    'created_at' => now(),
                ],
                RankType::cases(),
            ),
        );
    }
}
