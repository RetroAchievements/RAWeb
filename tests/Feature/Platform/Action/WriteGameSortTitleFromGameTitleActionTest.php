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

    public function testItGeneratesSortTitleBasedOnGameTitle(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($game, $game->title);
        $game = $game->fresh();

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
        $this->assertEquals('final fantasy 04', $game->sort_title);
    }

    public function testItCorrectlyHandlesGameTitlesWithTildes(): void
    {
        // Arrange
        $gameOne = Game::factory()->create(['Title' => '~Homebrew~ Classic Kong', 'sort_title' => null]);
        $gameTwo = Game::factory()->create(['Title' => 'Puyo Puyo~n', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameOne, $gameOne->title);
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameTwo, $gameTwo->title);
        $gameOne = $gameOne->fresh();
        $gameTwo = $gameTwo->fresh();

        // Assert
        $this->assertEquals('~homebrew classic kong', $gameOne->sort_title);
        $this->assertEquals('puyo puyo~n', $gameTwo->sort_title);
    }

    public function testItCorrectlyConvertsRomanNumerals(): void
    {
        // Arrange
        $gameOne = Game::factory()->create(['Title' => 'Final Fantasy IV', 'sort_title' => null]);
        $gameTwo = Game::factory()->create(['Title' => 'Final Fantasy X', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameOne, $gameOne->title);
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameTwo, $gameTwo->title);
        $gameOne = $gameOne->fresh();
        $gameTwo = $gameTwo->fresh();

        // Assert
        $this->assertEquals('final fantasy 04', $gameOne->sort_title);
        $this->assertEquals('final fantasy 10', $gameTwo->sort_title);
    }

    public function testItAvoidsNonRomanStrings(): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => 'GIVX', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($game, $game->title);
        $game = $game->fresh();

        // Assert
        $this->assertEquals('givx', $game->sort_title);
    }

    public function testItNormalizesArticleFragments(): void
    {
        // Arrange
        $gameOne = Game::factory()->create(['Title' => 'Legend of Zelda, The: A Link to the Past', 'sort_title' => null]);
        $gameTwo = Game::factory()->create(['Title' => 'American Tale, An', 'sort_title' => null]);
        $gameThree = Game::factory()->create(['Title' => 'Grand Day Out, A', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameOne, $gameOne->title);
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameTwo, $gameTwo->title);
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameThree, $gameThree->title);
        $gameOne = $gameOne->fresh();
        $gameTwo = $gameTwo->fresh();
        $gameThree = $gameThree->fresh();

        // Assert
        $this->assertEquals('legend of zelda: a link to the past', $gameOne->sort_title);
        $this->assertEquals('american tale', $gameTwo->sort_title);
        $this->assertEquals('grand day out', $gameThree->sort_title);
    }

    public function testItAvoidsNonArticleFragments(): void
    {
        // Arrange
        $gameOne = Game::factory()->create(['Title' => 'The Great Escape', 'sort_title' => null]);
        $gameTwo = Game::factory()->create(['Title' => 'An Unexpected Journey', 'sort_title' => null]);
        $gameThree = Game::factory()->create(['Title' => 'A Series of Unfortunate Events', 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameOne, $gameOne->title);
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameTwo, $gameTwo->title);
        (new WriteGameSortTitleFromGameTitleAction())->execute($gameThree, $gameThree->title);
        $gameOne = $gameOne->fresh();
        $gameTwo = $gameTwo->fresh();
        $gameThree = $gameThree->fresh();

        // Assert
        $this->assertEquals('the great escape', $gameOne->sort_title);
        $this->assertEquals('an unexpected journey', $gameTwo->sort_title);
        $this->assertEquals('a series of unfortunate events', $gameThree->sort_title);
    }
}
