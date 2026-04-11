<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Requests;

use App\Platform\Requests\GameListRequest;
use Tests\TestCase;

class GameListRequestTest extends TestCase
{
    public function testItUsesUrlValuesBeforeCookieValues(): void
    {
        // ARRANGE
        $cookiePreferences = $this->buildCookiePreferences();

        // ACT
        $request = GameListRequest::create('/games', 'GET', [
            'page' => ['size' => '100'],
            'sort' => '-title',
            'filter' => ['system' => '1'],
        ], [
            'datatable_view_preference_generic_games' => $cookiePreferences,
        ]);

        // ASSERT
        $this->assertSame(100, $request->getPageSize());
        $this->assertSame(['field' => 'title', 'direction' => 'desc'], $request->getSort());
        $this->assertSame([
            'system' => ['1'],
            'achievementsPublished' => ['has'],
        ], $request->getFilters());
    }

    public function testItUsesCookieValuesBeforeDefaults(): void
    {
        // ARRANGE
        $cookiePreferences = $this->buildCookiePreferences();

        // ACT
        $request = GameListRequest::create('/games', 'GET', [], [
            'datatable_view_preference_generic_games' => $cookiePreferences,
        ]);

        // ASSERT
        $this->assertSame(50, $request->getPageSize());
        $this->assertSame(['field' => 'lastUpdated', 'direction' => 'desc'], $request->getSort());
        $this->assertSame([
            'system' => ['2'],
            'achievementsPublished' => ['none'],
        ], $request->getFilters());
    }

    public function testItFallsBackToDefaultsWhenUrlAndCookieValuesAreAbsent(): void
    {
        // ACT
        $request = GameListRequest::create('/games', 'GET');

        // ASSERT
        $this->assertSame(25, $request->getPageSize());
        $this->assertSame(['field' => 'title', 'direction' => 'asc'], $request->getSort());
        $this->assertSame([
            'achievementsPublished' => ['has'],
        ], $request->getFilters());
    }

    private function buildCookiePreferences(): string
    {
        return json_encode([
            'pagination' => ['pageIndex' => 3, 'pageSize' => 50],
            'sorting' => [['id' => 'lastUpdated', 'desc' => true]],
            'columnFilters' => [
                ['id' => 'system', 'value' => ['2']],
                ['id' => 'achievementsPublished', 'value' => ['none']],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
