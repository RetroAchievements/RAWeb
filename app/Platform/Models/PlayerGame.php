<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerGame extends BasePivot
{
    use SoftDeletes;

    protected $table = 'player_games';

    protected $casts = [
        'last_played_at' => 'datetime',
        'completed_at' => 'datetime',
        'completed_hardcore_at' => 'datetime',
        'last_unlock_at' => 'datetime',
        'last_unlock_hardcore_at' => 'datetime',
        'first_unlock_at' => 'datetime',
        'first_unlock_hardcore_at' => 'datetime',
        'started_at' => 'datetime',
        'started_hardcore_at' => 'datetime',
        'metrics_updated_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return HasMany<Achievement>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'GameID', 'game_id');
    }

    /**
     * @return BelongsTo<Game, PlayerGame>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * @return BelongsTo<User, PlayerGame>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, PlayerGame>
     */
    public function player(): BelongsTo
    {
        return $this->user();
    }

    // == scopes
}
