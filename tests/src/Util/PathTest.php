<?php

declare(strict_types=1);

namespace Test\Util;

use Test\TestCase;

final class PathTest extends TestCase
{
    public function testPaths()
    {
        $this->assertStringStartsWith(base_path(), public_path());

        $this->assertPathEquals(base_path() . '\public\index.php', public_path('index.php'));
        $this->assertPathEquals(base_path() . '/public', base_path('public'));
    }
}
