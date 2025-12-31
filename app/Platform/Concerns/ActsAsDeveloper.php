<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Models\Achievement;
use App\Models\AchievementAuthor;
use App\Models\AchievementSetClaim;
use App\Models\Leaderboard;
use App\Models\MemoryNote;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ActsAsDeveloper
{
    public static function bootActsAsDeveloper(): void
    {
    }

    // == instance functions

    public function hasActiveClaimOnGameId(int $gameId): bool
    {
        return $this->loadMissing('achievementSetClaims')
            ->achievementSetClaims()
            ->where('game_id', $gameId)
            ->active()
            ->exists();
    }

    // == accessors

    // == relations

    /**
     * @return HasMany<AchievementSetClaim, $this>
     */
    public function achievementSetClaims(): HasMany
    {
        return $this->hasMany(AchievementSetClaim::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<Achievement, $this>
     */
    public function authoredAchievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<MemoryNote, $this>
     */
    public function authoredCodeNotes(): HasMany
    {
        return $this->hasMany(MemoryNote::class, 'user_id', 'id')->where('body', '!=', '');
    }

    /**
     * @return HasMany<Leaderboard, $this>
     */
    public function authoredLeaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'author_id', 'id');
    }

    /**
     * NOTE: this is the tickets the user has resolved (including ones not associated to their achievements).
     * @return HasMany<Ticket, $this>
     */
    public function resolvedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'resolver_id', 'id');
    }

    /**
     * @return HasMany<AchievementAuthor, $this>
     */
    public function achievementCredits(): HasMany
    {
        return $this->hasMany(AchievementAuthor::class, 'user_id', 'id');
    }

    // == scopes
}
