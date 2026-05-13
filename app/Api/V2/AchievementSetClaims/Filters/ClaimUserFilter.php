<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Actions\FindUserByIdentifierAction;
use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;

/**
 * Filters claims by their owning user.
 *
 * The supplied identifier may be a ULID, display name, or username.
 */
final class ClaimUserFilter implements Filter
{
    public function key(): string
    {
        return 'user';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function apply($query, $value)
    {
        $identifier = trim((string) $value);

        if ($identifier === '') {
            return $query;
        }

        $user = app(FindUserByIdentifierAction::class)->execute($identifier);

        if (!$user) {
            // Match no claims rather than 400. This mirrors how the User
            // resource silently returns an empty result for unknown identifiers.
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $user->id);
    }
}
