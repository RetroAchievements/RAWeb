<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectWarning extends BaseModel
{
    protected $table = 'connect_warnings';

    protected $fillable = [
        'method',
        'player_session_id',
        'username',
        'related_type',
        'related_id',
        'hardcore',
        'offset',
        'extra',
        'validation_hash',
        'smells',
        'user_agent',
    ];

    public const UPDATED_AT = null;

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<PlayerSession, $this>
     */
    public function playerSession(): BelongsTo
    {
        return $this->belongsTo(PlayerSession::class, 'player_session_id', 'id');
    }

    /**
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'related_id');
    }

    /**
     * @return BelongsTo<Leaderboard, $this>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class, 'related_id');
    }

    // == scopes
}
