<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\AwardType;
use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\PlayerGameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerGame extends BasePivot
{
    use SoftDeletes;
    /** @use HasFactory<PlayerGameFactory> */
    use HasFactory;

    protected $table = 'player_games';

    protected $casts = [
        'last_played_at' => 'datetime',
        'beaten_at' => 'datetime',
        'beaten_hardcore_at' => 'datetime',
        'beaten_dates' => 'json',
        'beaten_dates_hardcore' => 'json',
        'completed_at' => 'datetime',
        'completed_hardcore_at' => 'datetime',
        'completion_dates' => 'json',
        'completion_dates_hardcore' => 'json',
        'last_unlock_at' => 'datetime',
        'last_unlock_hardcore_at' => 'datetime',
        'first_unlock_at' => 'datetime',
        'first_unlock_hardcore_at' => 'datetime',
        'started_at' => 'datetime',
        'started_hardcore_at' => 'datetime',
        'metrics_updated_at' => 'datetime',
    ];

    protected static function newFactory(): PlayerGameFactory
    {
        return PlayerGameFactory::new();
    }

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
     * @return HasMany<PlayerBadge>
     */
    public function badges(): HasMany
    {
        return $this->hasMany(PlayerBadge::class, 'AwardData', 'game_id')
            ->whereIn('AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
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

    /**
     * @param Builder<PlayerGame> $query
     * @return Builder<PlayerGame>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->where('game_id', $game->id);
    }

    /**
     * @param Builder<PlayerGame> $query
     * @return Builder<PlayerGame>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
