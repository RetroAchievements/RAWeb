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

    /**
     * @dataProvider titleProvider
     */
    public function testItGeneratesCorrectSortTitles(string $gameTitle, string $expectedSortTitle): void
    {
        // Arrange
        $game = Game::factory()->create(['Title' => $gameTitle, 'sort_title' => null]);

        // Act
        (new WriteGameSortTitleFromGameTitleAction())->execute($game, $game->title);
        $game->refresh();

        // Assert
        $this->assertEquals($expectedSortTitle, $game->sort_title);
    }

    /**
     * @return string[][]
     */
    public static function titleProvider(): array
    {
        return [
            'Sonic the Hedgehog' => ['Sonic the Hedgehog', 'sonic the hedgehog'],
            'Final Fantasy IV' => ['Final Fantasy IV', 'final fantasy 04'],
            'Final Fantasy X' => ['Final Fantasy X', 'final fantasy 10'],
            'GIVX' => ['GIVX', 'givx'],
            'Legend of Zelda, The: A Link to the Past' => ['Legend of Zelda, The: A Link to the Past', 'legend of zelda: a link to the past'],
            'American Tale, An' => ['American Tale, An', 'american tale'],
            'Grand Day Out, A' => ['Grand Day Out, A', 'grand day out'],
            'The Great Escape' => ['The Great Escape', 'the great escape'],
            'An Unexpected Journey' => ['An Unexpected Journey', 'an unexpected journey'],
            'A Series of Unfortunate Events' => ['A Series of Unfortunate Events', 'a series of unfortunate events'],
            '~Homebrew~ Classic Kong' => ['~Homebrew~ Classic Kong', '~homebrew classic kong'],
            'Puyo Puyo~n' => ['Puyo Puyo~n', 'puyo puyo~n'],
            '~Hack~ V I T A L I T Y' => ['~Hack~ V I T A L I T Y', '~hack v i t a l i t y'],
            '~Hack~ Dragoon X Omega' => ['~Hack~ Dragoon X Omega', '~hack dragoon x omega'],
            '~Hack~ Pokemon - X and Y' => ['~Hack~ Pokemon - X and Y', '~hack pokemon - x and y'],
            'I Have No Mouth, And I Must Scream' => ['I Have No Mouth, And I Must Scream', 'i have no mouth, and i must scream'],
            "I'm Sorry" => ["I'm Sorry", "i'm sorry"],
        ];
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
}
