<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Models\User;
use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerAchievementSet extends BasePivot
{
    protected $table = 'player_achievement_sets';

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
     * @return HasMany<PlayerAchievement>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'game_id', 'game_id')
            ->where('player_games.user_id', '=', 'player_achievements.user_id');
    }

    /**
     * @return BelongsTo<AchievementSet, PlayerAchievementSet>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class, 'achievement_set_id');
    }

    /**
     * @return BelongsTo<User, PlayerAchievementSet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, PlayerAchievementSet>
     */
    public function player(): BelongsTo
    {
        return $this->user();
    }

    // == scopes
}
