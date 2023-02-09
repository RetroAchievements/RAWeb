<?php

declare(strict_types=1);

namespace Tests\Unit\Util;

use Tests\TestCase;

final class UrlTest extends TestCase
{
    public function testSeparateList()
    {
        $this->assertEquals('http://localhost/test.php', url('test.php'));
        $this->assertEquals('http://localhost/example.com/test', url('example.com/test'));
        $this->assertEquals('http://example.com/test', url('http://example.com/test'));
        $this->assertEquals('//example.com/test', url('//example.com/test'));
    }
}
