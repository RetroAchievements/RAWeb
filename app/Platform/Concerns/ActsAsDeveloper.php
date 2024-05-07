<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Models\Achievement;
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

    // == accessors

    // == relations

    /**
     * @return HasMany<AchievementSetClaim>
     */
    public function achievementSetClaims(): HasMany
    {
        return $this->hasMany(AchievementSetClaim::class, 'user_id', 'ID');
    }

    /**
     * @return HasMany<Achievement>
     */
    public function authoredAchievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'user_id', 'ID');
    }

    /**
     * @return HasMany<MemoryNote>
     */
    public function authoredCodeNotes(): HasMany
    {
        return $this->hasMany(MemoryNote::class, 'user_id', 'ID')->where('Note', '!=', '');
    }

    /**
     * @return HasMany<Leaderboard>
     */
    public function authoredLeaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'Author', 'User');
    }

    /**
     * NOTE: this is the tickets the user has resolved (including ones not associated to their achievements).
     * @return HasMany<Ticket>
     */
    public function resolvedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'resolver_id', 'ID');
    }

    // == scopes
}
