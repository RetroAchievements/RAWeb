<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Community\Enums\AwardType;
use App\Community\Enums\Rank;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\PlayerGlobalRanking;
use App\Models\PlayerSession;
use App\Models\PlayerStat;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameBadgePreference;
use App\Platform\Enums\GlobalRankingWindow;
use App\Platform\Enums\PlayerPreferredMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ActsAsPlayer
{
    public static function bootActsAsPlayer(): void
    {
    }

    // == instance methods

    public function hasMasteredGame(int $gameId): bool
    {
        return $this->playerBadges()
            ->where('award_type', AwardType::Mastery)
            ->where('award_key', $gameId)
            ->exists();
    }

    public function hasPlayedGame(Game $game): bool
    {
        return $this->playerGames()->where('game_id', $game->id)->exists();
    }

    public function hasPlayedGameForAchievement(Achievement $achievement): bool
    {
        return $this->playerGames()
            ->whereIn('game_id', $achievement->getRelatedGameIds())
            ->exists();
    }

    public function playerGame(Game $game): ?PlayerGame
    {
        return $this->playerGames()->where('game_id', $game->id)->first();
    }

    // == accessors

    public function getPointsRatioAttribute(): float|string
    {
        return $this->points_hardcore ? ($this->points_weighted / $this->points_hardcore) : 0;
    }

    public function getPlayerPreferredModeAttribute(): PlayerPreferredMode
    {
        // This attribute doesn't care if the user is untracked.
        $hasHardcoreRank = $this->points_hardcore >= Rank::MIN_POINTS;
        $hasCasualRank = $this->points >= Rank::MIN_POINTS;

        if ($hasHardcoreRank && $hasCasualRank) {
            return PlayerPreferredMode::Mixed;
        }

        if ($hasHardcoreRank) {
            return PlayerPreferredMode::Hardcore;
        }

        if ($hasCasualRank) {
            return PlayerPreferredMode::Casual;
        }

        // New players are defaulted to preferring hardcore.
        return PlayerPreferredMode::Hardcore;
    }

    // == relations

    /**
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements', 'user_id', 'achievement_id')
            ->using(PlayerAchievement::class);
    }

    /**
     * @return BelongsToMany<Game, $this>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'player_games', 'user_id', 'game_id')
            ->using(PlayerGame::class)
            ->withTimestamps('created_at', 'updated_at');
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function lastGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'rich_presence_game_id', 'id');
    }

    /**
     * @return HasMany<LeaderboardEntry, $this>
     */
    public function leaderboardEntries(): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<PlayerAchievement, $this>
     */
    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class, 'user_id');
    }

    /**
     * Return badges earned by the user
     *
     * @return HasMany<PlayerBadge, $this>
     */
    public function playerBadges(): HasMany
    {
        return $this->hasMany(PlayerBadge::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<PlayerGlobalRanking, $this>
     */
    public function globalRankings(): HasMany
    {
        return $this->hasMany(PlayerGlobalRanking::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<PlayerGlobalRanking, $this>
     */
    public function allTimeGlobalRankings(): HasMany
    {
        return $this->globalRankings()->where('window', GlobalRankingWindow::AllTime);
    }

    /**
     * Return the user's per-game chosen display badges.
     *
     * @return HasMany<UserGameBadgePreference, $this>
     */
    public function badgePreferences(): HasMany
    {
        return $this->hasMany(UserGameBadgePreference::class, 'user_id');
    }

    /**
     * @return HasMany<PlayerGame, $this>
     */
    public function playerGames(): HasMany
    {
        return $this->hasMany(PlayerGame::class, 'user_id');
    }

    /**
     * @return HasMany<PlayerAchievementSet, $this>
     */
    public function playerAchievementSets(): HasMany
    {
        return $this->hasMany(PlayerAchievementSet::class, 'user_id');
    }

    /**
     * @return HasMany<PlayerSession, $this>
     */
    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class, 'user_id');
    }

    /**
     * @return HasMany<PlayerStat, $this>
     */
    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerStat::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function reportedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reporter_id', 'id');
    }

    // == scopes

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeTracked(Builder $query): Builder
    {
        $query->whereNull('unranked_at');

        return $query;
    }

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeCurrentlyOnline(Builder $query): Builder
    {
        $recentMinutes = 10;
        $query->where('last_activity_at', '>', Carbon::now()->subMinutes($recentMinutes));

        return $query;
    }
}
