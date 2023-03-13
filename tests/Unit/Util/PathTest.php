<?php

declare(strict_types=1);

namespace Tests\Unit\Util;

use Tests\TestCase;

final class PathTest extends TestCase
{
    public function testPaths(): void
    {
        $this->assertStringStartsWith(base_path(), public_path());

        $this->assertPathEquals(base_path() . '\public\index.php', public_path('index.php'));
        $this->assertPathEquals(base_path() . '/public', base_path('public'));
    }
}
