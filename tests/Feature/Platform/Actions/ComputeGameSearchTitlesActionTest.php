<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Platform\Actions\ComputeGameSearchTitlesAction;
use Tests\TestCase;

class ComputeGameSearchTitlesActionTest extends TestCase
{
    /**
     * @dataProvider titleProvider
     */
    public function testItGeneratesCorrectSearchVariations(string $gameTitle, array $expectedVariations): void
    {
        // Act
        $searchTitles = (new ComputeGameSearchTitlesAction())->execute($gameTitle);

        // Assert
        foreach ($expectedVariations as $expectedVariation) {
            $this->assertContains($expectedVariation, $searchTitles);
        }
    }

    /**
     * @dataProvider titleWithAlternativesProvider
     */
    public function testItIncludesAlternativeTitles(string $gameTitle, array $altTitles, array $expectedVariations): void
    {
        // Act
        $searchTitles = (new ComputeGameSearchTitlesAction())->execute($gameTitle, $altTitles);

        // Assert
        foreach ($expectedVariations as $expectedVariation) {
            $this->assertContains($expectedVariation, $searchTitles);
        }
    }

    public function testItRemovesDuplicates(): void
    {
        // Arrange
        $gameTitle = 'Final Fantasy VII';
        $altTitles = ['Final Fantasy 7', 'Final Fantasy VII'];

        // Act
        $searchTitles = (new ComputeGameSearchTitlesAction())->execute($gameTitle, $altTitles);

        // Assert
        $uniqueTitles = array_unique($searchTitles);
        $this->assertCount(count($uniqueTitles), $searchTitles); // !! both "Final Fantasy VII" titles coalesce into one title
    }

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function titleProvider(): array
    {
        return [
            // Roman numeral conversions.
            'Final Fantasy VII' => [
                'Final Fantasy VII',
                ['final fantasy vii', 'Final Fantasy 7', 'final fantasy 7', 'ff7', 'ffvii', 'ff', 'final fantasy'],
            ],
            'Dragon Quest II' => [
                'Dragon Quest II',
                ['dragon quest ii', 'Dragon Quest 2', 'dragon quest 2', 'dq2', 'dqii', 'dq', 'dragon quest'],
            ],
            'Street Fighter III' => [
                'Street Fighter III',
                ['street fighter iii', 'Street Fighter 3', 'street fighter 3', 'sf3', 'sfiii', 'sf', 'street fighter'],
            ],

            // Number to Roman conversions.
            'Mega Man 2' => [
                'Mega Man 2',
                ['mega man 2', 'Mega Man II', 'mega man ii', 'mm2', 'mm', 'mega man'],
            ],
            'Final Fantasy 10' => [
                'Final Fantasy 10',
                ['final fantasy 10', 'Final Fantasy X', 'final fantasy x', 'ff10', 'ffx', 'ff', 'final fantasy'],
            ],

            // Series name extraction.
            'The Legend of Zelda: Ocarina of Time' => [
                'The Legend of Zelda: Ocarina of Time',
                ['zelda', 'loz', 'tloz', 'the legend of zelda ocarina of time', 'Legend of Zelda: Ocarina of Time'],
            ],
            'Super Mario 64' => [
                'Super Mario 64',
                ['super mario 64', 'sm64', 'super mario64', 'super mario', 'sm'],
            ],
            'Sonic the Hedgehog' => [
                'Sonic the Hedgehog',
                ['sonic the hedgehog', 'sonic'],
            ],

            // Nintendo 64 games.
            'Donkey Kong 64' => [
                'Donkey Kong 64',
                ['donkey kong 64', 'dk64', 'donkey kong64', 'dk', 'donkey kong'],
            ],
            'Star Fox 64' => [
                'Star Fox 64',
                ['star fox 64', 'sf64', 'star fox64', 'sf', 'star fox'],
            ],

            // Games with "The" at beginning.
            'The Matrix' => [
                'The Matrix',
                ['the matrix', 'Matrix', 'matrix'],
            ],
            'The Elder Scrolls V: Skyrim' => [
                'The Elder Scrolls V: Skyrim',
                ['elder scrolls', 'Elder Scrolls V: Skyrim', 'the elder scrolls v skyrim'],
            ],

            // Special characters and punctuation.
            "Luigi's Mansion" => [
                "Luigi's Mansion",
                ["luigi's mansion", "luigi s mansion"],
            ],
            'Pokémon Stadium' => [
                'Pokémon Stadium',
                ['pokémon stadium', 'pokemon', 'pok mon stadium'],
            ],

            // Multi-part series with numbers.
            'Kingdom Hearts II' => [
                'Kingdom Hearts II',
                ['kingdom hearts ii', 'Kingdom Hearts 2', 'kingdom hearts 2', 'kh2', 'khii', 'kh', 'kingdom hearts'],
            ],
            'Devil May Cry 3' => [
                'Devil May Cry 3',
                ['devil may cry 3', 'Devil May Cry III', 'devil may cry iii', 'dmc3', 'dmc', 'devil may cry'],
            ],

            // Games with hyphens and special formatting.
            'Mega Man X4' => [
                'Mega Man X4',
                ['mega man x4', 'mm', 'mega man'],
            ],
            'F-Zero' => [
                'F-Zero',
                ['f-zero', 'f zero', 'fz'],
            ],

            // Simple titles without series.
            'Tetris' => [
                'Tetris',
                ['tetris'],
            ],
            'Doom' => [
                'Doom',
                ['doom'],
            ],

            // Edge cases.
            'Grand Theft Auto Vice City' => [
                'Grand Theft Auto Vice City',
                ['grand theft auto vice city', 'gta', 'grand theft auto'],
            ],
            'Call of Duty: Modern Warfare 2' => [
                'Call of Duty: Modern Warfare 2',
                ['call of duty modern warfare 2', 'cod', 'call of duty'],
            ],
        ];
    }

    /**
     * @return array<string, array{string, string[], string[]}>
     */
    public static function titleWithAlternativesProvider(): array
    {
        return [
            // Game with regional title differences.
            'Dragon Warrior' => [
                'Dragon Warrior',
                ['Dragon Quest'],
                ['dragon warrior', 'dw', 'dragon quest', 'dq'],
            ],

            // Game with multiple alternative titles.
            'Final Fantasy VI' => [
                'Final Fantasy VI',
                ['Final Fantasy III', 'Final Fantasy 6'],
                ['final fantasy vi', 'final fantasy 6', 'ff6', 'ffvi', 'final fantasy iii', 'final fantasy 3', 'ff3', 'ffiii', 'final fantasy'],
            ],

            // Game with subtitle variations.
            'The Legend of Zelda: A Link to the Past' => [
                'The Legend of Zelda: A Link to the Past',
                ['Zelda no Densetsu: Kamigami no Triforce'],
                ['zelda', 'loz', 'tloz', 'zelda no densetsu kamigami no triforce', 'Legend of Zelda: A Link to the Past'],
            ],

            // Game with completely different regional name.
            'Resident Evil' => [
                'Resident Evil',
                ['Biohazard'],
                ['resident evil', 're', 'biohazard'],
            ],
        ];
    }
}
