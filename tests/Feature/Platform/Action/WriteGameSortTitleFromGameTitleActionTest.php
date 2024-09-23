<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Game;
use App\Platform\Actions\WriteGameSortTitleFromGameTitleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WriteGameSortTitleFromGameTitleActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItGeneratesCorrectSortTitles(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($game, $game->title);
        $game->refresh();

        // Assert
        $this->assertEquals('sonic the hedgehog', $game->sort_title);
    }

    public function testItPreservesCustomSortTitlesByDefault(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Final Fantasy IV', 'sort_title' => 'final fantasy 04']);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($game, $game->title);
        $game = $game->fresh();

        // Assert
        $this->assertEquals('final fantasy 04', $game->sort_title);
    }

    public function testItCanBeConfiguredToOverrideCustomSortTitles(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Final Fantasy IV', 'sort_title' => 'final fantasy 0004']);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute(
            $game,
            $game->title,
            shouldRespectCustomSortTitle: false,
        );
        $game = $game->fresh();

        // Assert
        $this->assertEquals('final fantasy 00004', $game->sort_title);
    }
}
