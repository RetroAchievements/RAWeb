<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Leaderboard;
use App\Models\MemoryNote;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ActsAsDeveloper
{
    public static function bootActsAsDeveloper(): void
    {
    }

    // == accessors

    // == relations

    /**
     * @return HasMany<Achievement>
     */
    public function authoredAchievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'Author', 'User');
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
     * @return HasMany<AchievementSetClaim>
     */
    public function achievementSetClaims(): HasMany
    {
        return $this->hasMany(AchievementSetClaim::class, 'User', 'User');
    }

    // == scopes
}
