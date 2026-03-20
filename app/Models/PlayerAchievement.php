<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\PlayerAchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;

class PlayerAchievement extends BasePivot
{
    /** @use HasFactory<PlayerAchievementFactory> */
    use HasFactory;

    protected $table = 'player_achievements';

    public const CREATED_AT = 'unlocked_at';
    public const UPDATED_AT = null;

    protected static function newFactory(): PlayerAchievementFactory
    {
        return PlayerAchievementFactory::new();
    }

    protected $fillable = [
        'user_id',
        'achievement_id',
        'trigger_id',
        'player_session_id',
        'unlocked_at',
        'unlocked_hardcore_at',
        'unlocker_id',
    ];

    protected $guarded = [
        'unlocked_effective_at', // virtual column managed by the DB engine, we can't write to this
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'unlocked_hardcore_at' => 'datetime',
        'unlocked_effective_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function player(): BelongsTo
    {
        return $this->user();
    }

    /**
     * @return HasOneThrough<Game, Achievement, $this>
     */
    public function game(): HasOneThrough
    {
        return $this->hasOneThrough(
            Game::class,
            Achievement::class,
            'id',             // FK on achievements (its primary key)
            'id',             // FK on games (its primary key)
            'achievement_id', // Local key on player_achievements
            'game_id'         // Local key on achievements
        );
    }

    // == scopes

    /**
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->whereHas('achievement', function ($query) use ($game) {
            $query->where('game_id', $game->id);
        });
    }

    /**
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function scopeForGameId(Builder $query, int $gameId): Builder
    {
        return $query->whereHas('achievement', fn (Builder $q) => $q->where('achievements.game_id', $gameId));
    }

    /**
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function scopeForAchievementSetId(Builder $query, int $achievementSetId): Builder
    {
        return $query->whereHas('achievement', function (Builder $q) use ($achievementSetId) {
            $q->whereExists(function ($sub) use ($achievementSetId) {
                $sub->select(DB::raw(1))
                    ->from('achievement_set_achievements')
                    ->whereColumn('achievement_set_achievements.achievement_id', 'achievements.id')
                    ->where('achievement_set_achievements.achievement_set_id', $achievementSetId);
            });
        });
    }

    /**
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function scopeUnlockedFrom(Builder $query, string $date): Builder
    {
        return $query->where('unlocked_at', '>=', $date);
    }

    /**
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function scopeUnlockedTo(Builder $query, string $date): Builder
    {
        return $query->where('unlocked_at', '<=', $date);
    }

    /**
     * @param Builder<PlayerAchievement> $query
     * @return Builder<PlayerAchievement>
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query
            ->addSelect('player_achievements.*')
            ->leftJoin('unranked_users', 'player_achievements.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id');
    }
}
