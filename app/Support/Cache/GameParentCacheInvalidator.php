<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Models\GameAchievementSet;
use Illuminate\Support\Facades\Cache;

class GameParentCacheInvalidator
{
    /**
     * Flush the cached parent_game_id for the given game and, if an achievement
     * set is supplied, for every other game that shares that set.
     */
    public static function invalidate(?int $gameId, ?int $achievementSetId = null): void
    {
        if ($gameId !== null) {
            Cache::forget(CacheKey::buildGameParentIdCacheKey($gameId));
        }

        if ($achievementSetId === null) {
            return;
        }

        $sharedGameIds = GameAchievementSet::query()
            ->where('achievement_set_id', $achievementSetId)
            ->pluck('game_id');

        foreach ($sharedGameIds as $sharedGameId) {
            if ($sharedGameId === $gameId) {
                continue;
            }

            Cache::forget(CacheKey::buildGameParentIdCacheKey((int) $sharedGameId));
        }
    }
}
