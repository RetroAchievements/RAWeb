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
    public function scopeForUserIdentifier(Builder $query, string $identifier): Builder
    {
        $user = app(FindUserByIdentifierAction::class)->execute($identifier);

        if (!$user) {
            return $query->whereRaw('1 = 0'); // no match, return no results
        }

        return $query->where('user_id', $user->id);
    }
}
