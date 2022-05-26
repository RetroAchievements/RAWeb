<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;

final class UtilTest extends TestCase
{
    public function testSeparateList()
    {
        $expected = ['x', 10, 20, 30, 40, 50];

        $this->assertEquals($expected, separateList("	x,10;20 30|40	50,	"));
        $this->assertEquals($expected, separateList('x, 10, 20, 30, 40, 50'));
        $this->assertEquals($expected, separateList("x	10	20	30	40	50"));
        $this->assertEquals($expected, separateList('x|10|20|30|40|50'));
        $this->assertEquals($expected, separateList('x;10;20;30;40;50'));
    }
}
