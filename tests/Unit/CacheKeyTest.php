<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Community\Enums\RankType;
use App\Platform\Enums\AchievementType;
use App\Support\Cache\CacheKey;
use Tests\TestCase;

final class CacheKeyTest extends TestCase
{
    public function testBuildUserCanTicketCacheKey(): void
    {
        $userName = "UserName";

        $cacheKey = CacheKey::buildUserCanTicketCacheKey($userName);

        $this->assertEquals("user:username:canTicket", $cacheKey);
    }

    public function testBuildUserCardDataCacheKey(): void
    {
        $userName = "UserName";

        $cacheKey = CacheKey::buildUserCardDataCacheKey($userName);

        $this->assertEquals("user:username:card-data", $cacheKey);
    }

    public function testBuildUserGameUnlocksCacheKey(): void
    {
        $userName = "UserName";
        $gameID = 14402;
        $flags = AchievementType::OfficialCore;

        $cacheKey = CacheKey::buildUserGameUnlocksCacheKey($userName, $gameID, $flags);

        $this->assertEquals("user:username:gameUnlocks:14402:" . AchievementType::OfficialCore, $cacheKey);
    }

    public function testBuildUserRankCacheKey(): void
    {
        $userName = "UserName";
        $rankType = RankType::Softcore;

        $cacheKey = CacheKey::buildUserRankCacheKey($userName, $rankType);

        $this->assertEquals("user:username:rank:softcore", $cacheKey);
    }

    public function testBuildUserRecentGamesCacheKey(): void
    {
        $userName = "UserName";

        $cacheKey = CacheKey::buildUserRecentGamesCacheKey($userName);

        $this->assertEquals("user:username:recentGames", $cacheKey);
    }
}
