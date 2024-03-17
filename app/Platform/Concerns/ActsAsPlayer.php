<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Connect\Controllers\ConnectApiController;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\Ticket;
use App\Models\User;
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
            $this->appToken = Str::random(30);
        } while ($this->where('appToken', $this->appToken)->exists());
        $this->appTokenExpiry = Carbon::now()->addDays(ConnectApiController::TOKEN_EXPIRY_DAYS);
        $this->save();
    }

    // == instance methods

    public function hasPlayed(Game $game): bool
    {
        return $this->playerGame($game)->exists();
    }

    public function playerGame(Game $game): ?PlayerGame
    {
        return $this->playerGames()->where('game_id', $game->id)->first();
    }

    // == accessors

    public function getPointsRatioAttribute(): float|string
    {
        return $this->points ? ($this->points_weighted / $this->points) : 0;
    }

    // == relations

    /**
     * @return BelongsToMany<Achievement>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements', 'user_id', 'achievement_id')
            ->using(PlayerAchievement::class);
    }

    /**
     * @return BelongsToMany<Game>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'player_games', 'user_id', 'game_id')
            ->using(PlayerGame::class)
            ->withTimestamps('created_at', 'updated_at');
    }

    /**
     * @return BelongsTo<Game, User>
     */
    public function lastGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'LastGameID', 'ID');
    }

    /**
     * @return HasMany<PlayerAchievement>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'user_id');
    }

    /**
     * Return badges earned by the user
     *
     * @return HasMany<PlayerBadge>
     */
    public function playerBadges(): HasMany
    {
        return $this->hasMany(PlayerBadge::class, 'User', 'User');
    }

    /**
     * @return HasMany<PlayerSession>
     */
    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class, 'user_id');
    }

    /**
     * @return HasMany<PlayerGame>
     */
    public function playerGames(): HasMany
    {
        return $this->hasMany(PlayerGame::class, 'user_id');
    }

    /**
     * @return HasMany<Ticket>
     */
    public function reportedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reporter_id', 'ID');
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
            $query->where('Untracked', false);
            if (request()->user()) {
                $query->orWhere('User', '=', request()->user()->username);
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
        $query->where('LastLogin', '>', Carbon::now()->subMinutes($recentMinutes));

        return $query;
    }
}
