<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Sorts;

use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\SortField;

/**
 * Sorts claims alphabetically by the claimant user's display name.
 */
final class SortByUserDisplayName implements SortField
{
    public function sortField(): string
    {
        return 'userDisplayName';
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function sort($query, string $direction = 'asc')
    {
        return $query
            ->leftJoin('users', 'users.id', '=', 'achievement_set_claims.user_id')
            ->orderBy('users.display_name', $direction)
            ->select('achievement_set_claims.*');
    }
}
