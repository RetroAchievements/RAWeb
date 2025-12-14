<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\GameSet;
use App\Platform\Actions\WriteGameSetSortTitleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WriteGameSetSortTitleActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItGeneratesCorrectSortTitles(): void
    {
        // Arrange
        $gameSet = GameSet::factory()->create(['title' => '[Series - The Legend of Zelda]', 'sort_title' => null]);

        // Act
        (new WriteGameSetSortTitleAction())->execute($gameSet, $gameSet->title);
        $gameSet->refresh();

        // Assert
        $this->assertEquals('series - the legend of zelda', $gameSet->sort_title);
    }

    public function testItPreservesCustomSortTitlesByDefault(): void
    {
        // Arrange
        $gameSet = GameSet::factory()->create(['title' => '[Series - Final Fantasy]', 'sort_title' => 'series - final fantasy']);
        $gameSet = $gameSet->fresh();

        $gameSet->sort_title = 'ff-series';
        $gameSet->save();

        // Act
        (new WriteGameSetSortTitleAction())->execute($gameSet, $gameSet->title);
        $gameSet = $gameSet->fresh();

        // Assert
        $this->assertEquals('ff-series', $gameSet->sort_title);
    }

    public function testItCanBeConfiguredToOverrideCustomSortTitles(): void
    {
        // Arrange
        $gameSet = GameSet::factory()->create(['title' => '[Series - Final Fantasy]', 'sort_title' => 'ff-series']);

        // Act
        (new WriteGameSetSortTitleAction())->execute(
            $gameSet,
            $gameSet->title,
            shouldRespectCustomSortTitle: false,
        );
        $gameSet = $gameSet->fresh();

        // Assert
        $this->assertEquals('series - final fantasy', $gameSet->sort_title);
    }
}
