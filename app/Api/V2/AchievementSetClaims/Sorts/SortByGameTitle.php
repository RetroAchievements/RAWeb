<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Sorts;

use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\SortField;

/**
 * Sorts claims alphabetically by their associated game's canonical title.
 */
final class SortByGameTitle implements SortField
{
    public function sortField(): string
    {
        return 'gameTitle';
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function sort($query, string $direction = 'asc')
    {
        return $query
            ->leftJoin('games', 'games.id', '=', 'achievement_set_claims.game_id')
            ->orderBy('games.title', $direction)
            ->select('achievement_set_claims.*');
    }
}
