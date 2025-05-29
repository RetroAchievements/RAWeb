<?php

declare(strict_types=1);

namespace App\Platform\Services;

use Illuminate\Support\Facades\Cache;

class SearchIndexingService
{
    private const GAMES_CACHE_KEY = 'games_pending_search_index';
    private const ACHIEVEMENTS_CACHE_KEY = 'achievements_pending_search_index';
    private const CACHE_TTL = 86400; // 24 hours - enough buffer for twice-daily runs

    public function queueGameForIndexing(int $gameId): void
    {
        $this->queueItemForIndexing(self::GAMES_CACHE_KEY, $gameId);
    }

    public function queueAchievementForIndexing(int $achievementId): void
    {
        $this->queueItemForIndexing(self::ACHIEVEMENTS_CACHE_KEY, $achievementId);
    }

    public function getPendingGames(): array
    {
        return array_keys(Cache::get(self::GAMES_CACHE_KEY, []));
    }

    public function getPendingAchievements(): array
    {
        return array_keys(Cache::get(self::ACHIEVEMENTS_CACHE_KEY, []));
    }

    public function clearPendingGames(): void
    {
        Cache::forget(self::GAMES_CACHE_KEY);
    }

    public function clearPendingAchievements(): void
    {
        Cache::forget(self::ACHIEVEMENTS_CACHE_KEY);
    }

    private function queueItemForIndexing(string $cacheKey, int $itemId): void
    {
        $pendingItems = Cache::get($cacheKey, []);
        $pendingItems[$itemId] = now()->timestamp;

        Cache::put($cacheKey, $pendingItems, self::CACHE_TTL);
    }
}
