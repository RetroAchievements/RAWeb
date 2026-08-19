<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

/**
 * Finds the author IDs whose forum posts a viewer does not want to see.
 * Moderators get no exemption. This action also removes team accounts
 * from the set of users.
 */
class GetMaskedForumAuthorIdsAction
{
    /**
     * @return array<int, int>
     */
    public function execute(?User $viewer): array
    {
        if (!$viewer) {
            return [];
        }

        return Cache::remember(
            CacheKey::buildUserMaskedForumAuthorIdsCacheKey($viewer->id),
            now()->addMinutes(5),
            function () use ($viewer): array {
                $blockedIds = $viewer->blockedUsers()->pluck('users.id')->all();

                if (empty($blockedIds)) {
                    return [];
                }

                // Official team communication stays visible no matter who a
                // viewer has blocked. Team accounts speak for the website itself.
                $teamAccountIds = User::whereIn('username', array_keys(config('teams.accounts', [])))
                    ->pluck('id')
                    ->all();

                return array_values(array_map(
                    'intval',
                    array_diff($blockedIds, $teamAccountIds),
                ));
            },
        );
    }
}
