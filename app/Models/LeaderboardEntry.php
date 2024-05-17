<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\LeaderboardEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaderboardEntry extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'leaderboard_entries';

    protected $fillable = [
        'leaderboard_id',
        'user_id',
        'score',
        'trigger_id',
        'player_session_id',
    ];

    protected static function newFactory(): LeaderboardEntryFactory
    {
        return LeaderboardEntryFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Leaderboard, LeaderboardEntry>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class, 'leaderboard_id', 'ID');
    }

    /**
     * @return BelongsTo<PlayerSession, LeaderboardEntry>
     */
    public function playerSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class);
    }

    /**
     * @return BelongsTo<Trigger, LeaderboardEntry>
     */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    /**
     * @return BelongsTo<User, LeaderboardEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
