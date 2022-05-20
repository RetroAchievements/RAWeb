<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Connect\Controllers\ConnectApiController;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BasePivot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

trait ActsAsPlayer
{
    public static function bootActsAsPlayer(): void
    {
    }

    /**
     * Roll Connect API Key
     * For RetroArch this key may not be longer than 31 characters, let's go with 30 then
     */
    public function rollConnectToken(): void
    {
        do {
            $this->connect_token = Str::random(30);
        } while ($this->where('connect_token', $this->connect_token)->exists());
        $this->connect_token_expires_at = Carbon::now()->addDays(ConnectApiController::TOKEN_EXPIRY_DAYS);
        $this->save();
    }

    // == accessors

    public function getPointsRatioAttribute(): float|string
    {
        return $this->points_total ? ($this->points_weighted / $this->points_total) : 0;
    }

    public function getPointsTotalAttribute(): int
    {
        return (int) ($this->attributes['points_total'] ?? 0);
    }

    public function getPointsWeightedTotalAttribute(): int
    {
        return (int) ($this->attributes['points_weighted'] ?? 0);
    }

    // == relations

    /**
     * @return BelongsToMany<Achievement>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements')
            ->using(BasePivot::class)
            ->using(PlayerAchievement::class);
    }

    /**
     * @return HasMany<PlayerAchievement>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class);
    }

    /**
     * @return BelongsToMany<Game>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'player_games')
            ->using(PlayerGame::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<PlayerSession>
     */
    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    /**
     * @return BelongsTo<Game, User>
     */
    public function lastGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'last_game_id');
    }

    /**
     * @return HasMany<PlayerGame>
     */
    public function playerGames(): HasMany
    {
        return $this->hasMany(PlayerGame::class)
            ->leftJoin('games', 'games.id', '=', 'player_games.game_id');
    }

    public function playerGame(Game $game): ?PlayerGame
    {
        return $this->playerGames()->where('game_id', $game->id)->first();
    }

    public function hasPlayed(Game $game): bool
    {
        return $this->playerGame($game)->exists();
    }

    // == scopes

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeTracked(Builder $query): Builder
    {
        $query->where(function ($query) {
            /* @var Builder $query */
            $query->where('unranked', false);
            if (request()->user()) {
                $query->orWhere('username', '=', request()->user()->username);
            }
        });

        return $query;
    }

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeCurrentlyOnline(Builder $query): Builder
    {
        $recentMinutes = 10;
        $query->where('last_login_at', '>', Carbon::now()->subMinutes($recentMinutes));

        return $query;
    }
}
