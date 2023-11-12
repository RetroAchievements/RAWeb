<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Platform\Models\Achievement;
use App\Platform\Models\Leaderboard;
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
     * @return HasMany<Leaderboard>
     */
    public function authoredLeaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'Author', 'User');
    }

    // == scopes
}
