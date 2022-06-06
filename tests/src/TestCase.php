<?php

namespace Test;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function assertPathEquals(string $expected, string $actual, string $message = ''): void
    {
        $this->assertEquals(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $expected), $actual, $message);
    }
}
