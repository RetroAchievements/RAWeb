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
        'last_unlock_at' => 'datetime',
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
     * @return BelongsTo<Game, PlayerGame>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, PlayerGame>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
