<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\AchievementSetType;
use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\PlayerAchievementSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class PlayerAchievementSet extends BasePivot
{
    /** @use HasFactory<PlayerAchievementSetFactory> */
    use HasFactory;

    protected $table = 'player_achievement_sets';

    protected $casts = [
        'completed_at' => 'datetime',
        'completed_hardcore_at' => 'datetime',
        'completion_dates' => 'json',
        'completion_dates_hardcore' => 'json',
        'last_unlock_at' => 'datetime',
        'last_unlock_hardcore_at' => 'datetime',
    ];

    protected static function newFactory(): PlayerAchievementSetFactory
    {
        return PlayerAchievementSetFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return HasMany<PlayerAchievement, $this>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'game_id', 'game_id')
            ->where('player_games.user_id', '=', 'player_achievements.user_id');
    }

    /**
     * @return BelongsTo<AchievementSet, $this>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class, 'achievement_set_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function player(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Prefer the non-core attachment so we return the
     * parent game rather than the subset backing game.
     *
     * @return HasOneThrough<Game, GameAchievementSet, $this>
     */
    public function game(): HasOneThrough
    {
        return $this->hasOneThrough(
            Game::class,
            GameAchievementSet::class,
            'achievement_set_id', // FK on game_achievement_sets
            'id',                 // FK on games (its primary key)
            'achievement_set_id', // Local key
            'game_id'             // Local key
        )->orderByRaw("CASE WHEN game_achievement_sets.type != ? THEN 0 ELSE 1 END", [AchievementSetType::Core->value]);
    }

    // == scopes

    /**
     * @param Builder<PlayerAchievementSet> $query
     * @return Builder<PlayerAchievementSet>
     */
    public function scopeForGameId(Builder $query, int $gameId): Builder
    {
        return $query->whereHas('game', fn (Builder $q) => $q->where('games.id', $gameId));
    }
}
