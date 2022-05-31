<?php

declare(strict_types=1);

namespace Test\Util;

use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    public function testPaths()
    {
        $this->assertStringStartsWith(base_path(), public_path());
        $this->assertEquals(base_path() . '/public/index.php', public_path('index.php'));
        $this->assertEquals(base_path() . '/public', base_path('public'));
    }
}
