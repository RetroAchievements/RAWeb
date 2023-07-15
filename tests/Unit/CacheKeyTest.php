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
        $username = "UserName";

        $cacheKey = CacheKey::buildUserCanTicketCacheKey($username);

        $this->assertEquals("user:username:canTicket", $cacheKey);
    }

    public function testBuildUserCardDataCacheKey(): void
    {
        $username = "UserName";

        $cacheKey = CacheKey::buildUserCardDataCacheKey($username);

        $this->assertEquals("user:username:card-data", $cacheKey);
    }

    public function testBuildUserGameUnlocksCacheKey(): void
    {
        $username = "UserName";
        $gameID = 14402;
        $flags = AchievementType::OfficialCore;

        $cacheKey = CacheKey::buildUserGameUnlocksCacheKey($username, $gameID, $flags);

        $this->assertEquals("user:username:gameUnlocks:14402:" . AchievementType::OfficialCore, $cacheKey);
    }

    public function testBuildUserRankCacheKey(): void
    {
        $username = "UserName";
        $rankType = RankType::Softcore;

        $cacheKey = CacheKey::buildUserRankCacheKey($username, $rankType);

        $this->assertEquals("user:username:rank:softcore", $cacheKey);
    }

    public function testBuildUserRecentGamesCacheKey(): void
    {
        $username = "UserName";

        $cacheKey = CacheKey::buildUserRecentGamesCacheKey($username);

        $this->assertEquals("user:username:recentGames", $cacheKey);
    }
}
