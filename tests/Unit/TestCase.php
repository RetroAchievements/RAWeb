<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function assertPathEquals(string $expected, string $actual, string $message = ''): void
    {
        $this->assertEquals(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $expected), $actual, $message);
    }
}
