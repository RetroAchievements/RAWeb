<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerSession extends BasePivot
{
    protected $table = 'player_sessions';

    protected $fillable = [
        'user_id',
        'game_id',
        'game_hash_id',
        'game_hash_set_id',
        'hardcore',
        'duration',
        'rich_presence',
        'rich_presence_updated_at',
        // necessary for UpdatePlayerGameSessions action to backdate
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'rich_presence_updated_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return HasMany<Achievement>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'game_id', 'game_id');
    }

    /**
     * @return HasMany<PlayerAchievement>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'game_id', 'game_id')
            ->where('player_games.user_id', '=', 'player_achievements.user_id');
    }

    /**
     * @return BelongsTo<Game, PlayerSession>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, PlayerSession>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // == scopes
}
