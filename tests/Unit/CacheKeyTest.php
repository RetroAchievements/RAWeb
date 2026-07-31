<?php

declare(strict_types=1);

namespace Tests\Unit;

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
}
