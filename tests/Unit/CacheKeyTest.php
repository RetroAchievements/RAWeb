<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Community\Enums\RankType;
use App\Support\Cache\CacheKey;
use Tests\TestCase;

final class CacheKeyTest extends TestCase
{
    public function testBuildUserCardDataCacheKey(): void
    {
        $username = "UserName";

        $cacheKey = CacheKey::buildUserCardDataCacheKey($username);

        $this->assertEquals("user:username:card-data", $cacheKey);
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

        $this->assertEquals("user:username:recent-games", $cacheKey);
    }
}
