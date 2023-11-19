<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Url\UrlBuilder;
use Tests\TestCase;

final class UrlBuilderTest extends TestCase
{
    public function testPrettifiesQueryParams(): void
    {
        $originalQueryParams = [
            'page' => ['number' => 2],
            'filter' => ['status' => 'unawarded'],
            'sort' => 'pct_won',
        ];

        $prettifiedQueryParams = UrlBuilder::prettyHttpBuildQuery($originalQueryParams);

        $this->assertEquals("page[number]=2&filter[status]=unawarded&sort=pct_won", $prettifiedQueryParams);
    }
}
