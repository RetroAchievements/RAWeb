<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Filament\Actions\ParseIdsFromCsvAction;
use Tests\TestCase;

class ParseIdsFromCsvActionTest extends TestCase
{
    private ParseIdsFromCsvAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new ParseIdsFromCsvAction();
    }

    public function testItParsesCommaSeparatedIds(): void
    {
        // Arrange
        $input = '1,2,3,4,5';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    public function testItParsesSpaceSeparatedIds(): void
    {
        // Arrange
        $input = '1 2 3 4 5';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    public function testItParsesMixedSeparationIds(): void
    {
        // Arrange
        $input = '1, 2 3,4  5';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    public function testItParsesIdsFromFullUrls(): void
    {
        // Arrange
        $input = 'https://retroachievements.org/game/1 https://retroachievements.org/game/2';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2], $result);
    }

    public function testItParsesIdsFromRelativePaths(): void
    {
        // Arrange
        $input = '/hub/1 /hub/2';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2], $result);
    }

    public function testItParsesIdsFromSimplePaths(): void
    {
        // Arrange
        $input = 'hub/1 hub/2 hub/3';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testItRemovesDuplicateIds(): void
    {
        // Arrange
        $input = '1,1,2,3,3,3,4,5,5';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    public function testItReturnsEmptyArrayForEmptyInput(): void
    {
        // Arrange
        $input = '';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([], $result);
    }

    public function testItReturnsEmptyArrayForNonNumericInput(): void
    {
        // Arrange
        $input = 'abc,def,ghi';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([], $result);
    }

    public function testItFiltersOutNonNumericValues(): void
    {
        // Arrange
        $input = '1,abc,2,def,3';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testItHandlesMixedFormatInput(): void
    {
        // Arrange
        $input = '1, 2, game/3, /achievement/4, https://retroachievements.org/game/5, abc, 6';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3, 4, 5, 6], $result);
    }

    public function testItHandlesTrailingSlashesInUrls(): void
    {
        // Arrange
        $input = 'game/1/ /achievement/2/ https://retroachievements.org/game/3/';

        // Act
        $result = $this->action->execute($input);

        // Assert
        $this->assertEquals([1, 2, 3], $result);
    }
}
