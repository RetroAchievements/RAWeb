<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\PlayerSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerSession extends BasePivot
{
    /*
     * Framework Traits
     */
    /** @use HasFactory<PlayerSessionFactory> */
    use HasFactory;

    protected $table = 'player_sessions';

    protected $fillable = [
        'user_id',
        'game_id',
        'game_hash_id',
        'game_hash_set_id',
        'duration',
        'rich_presence',
        'rich_presence_updated_at',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'rich_presence_updated_at' => 'datetime',
    ];

    protected static function newFactory(): PlayerSessionFactory
    {
        return PlayerSessionFactory::new();
    }

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
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<GameHash, PlayerSession>
     */
    public function gameHash(): BelongsTo
    {
        return $this->belongsTo(GameHash::class);
    }

    /**
     * @return BelongsTo<User, PlayerSession>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
