<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\FindUserByIdentifierAction;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\LeaderboardEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class LeaderboardEntry extends BaseModel
{
    /** @use HasFactory<LeaderboardEntryFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'leaderboard_entries';

    protected $fillable = [
        'leaderboard_id',
        'user_id',
        'score',
        'trigger_id',
        'player_session_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function newFactory(): LeaderboardEntryFactory
    {
        return LeaderboardEntryFactory::new();
    }

    // == helpers

    /**
     * Correlated subquery computing an entry's rank. Only valid inside a query
     * whose outer table is `leaderboard_entries` without an alias.
     *
     * @return Builder<LeaderboardEntry>
     */
    public static function rankSubquery(): Builder
    {
        return self::betterScoringEntriesSubquery()
            ->selectRaw('COUNT(*) + 1');
    }

    /**
     * Correlated subquery matching entries with strictly better scores from
     * ranked, non-deleted users. Only valid inside a query whose outer table
     * is `leaderboard_entries` without an alias.
     *
     * @return Builder<LeaderboardEntry>
     */
    private static function betterScoringEntriesSubquery(): Builder
    {
        return self::from('leaderboard_entries as entries_rank_calc')
            ->join('leaderboards as leaderboard_rank_calc', 'entries_rank_calc.leaderboard_id', '=', 'leaderboard_rank_calc.id')
            ->whereColumn('entries_rank_calc.leaderboard_id', 'leaderboard_entries.leaderboard_id')
            ->whereNotExists(function ($sub) {
                $sub->select('user_id')
                    ->from('unranked_users')
                    ->whereColumn('unranked_users.user_id', 'entries_rank_calc.user_id');
            })
            ->whereNull('entries_rank_calc.deleted_at')
            ->where(function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where(DB::raw('leaderboard_rank_calc.rank_asc'), 1)
                        ->whereColumn('entries_rank_calc.score', '<', 'leaderboard_entries.score');
                })->orWhere(function (Builder $query) {
                    $query->where(DB::raw('leaderboard_rank_calc.rank_asc'), 0)
                        ->whereColumn('entries_rank_calc.score', '>', 'leaderboard_entries.score');
                });
            });
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Leaderboard, $this>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class, 'leaderboard_id');
    }

    /**
     * @return BelongsTo<PlayerSession, $this>
     */
    public function playerSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'player_session_id', 'id');
    }

    /**
     * @return BelongsTo<Trigger, $this>
     */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes

    /**
     * @param Builder<LeaderboardEntry> $query
     * @return Builder<LeaderboardEntry>
     */
    public function scopeForGameIds(Builder $query, string $gameIds): Builder
    {
        $ids = collect(explode(',', $gameIds))
            ->map(fn (string $id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return $query->whereRaw('1 = 0'); // no valid IDs, return no results
        }

        return $query->whereHas('leaderboard', fn (Builder $q) => $q->whereIn('game_id', $ids));
    }

    /**
     * @param Builder<LeaderboardEntry> $query
     * @return Builder<LeaderboardEntry>
     */
    public function scopeForUserIdentifier(Builder $query, string $identifier): Builder
    {
        $user = app(FindUserByIdentifierAction::class)->execute($identifier);

        if (!$user) {
            return $query->whereRaw('1 = 0'); // no match, return no results
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * @param Builder<LeaderboardEntry> $query
     * @return Builder<LeaderboardEntry>
     */
    public function scopeForMaxRank(Builder $query, mixed $maxRank): Builder
    {
        $maxRank = filter_var($maxRank, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($maxRank === false) {
            return $query->whereRaw('1 = 0'); // invalid rank, return no results
        }

        return $query->whereNotExists(
            self::betterScoringEntriesSubquery()
                ->selectRaw('1')
                ->limit(1)
                ->offset($maxRank - 1)
        );
    }
}
