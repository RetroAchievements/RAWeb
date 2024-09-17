<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Platform\Actions\ComputeGameSortTitleAction;
use Tests\TestCase;

class ComputeGameSortTitleActionTest extends TestCase
{
    /**
     * @dataProvider titleProvider
     */
    public function testItGeneratesCorrectSortTitles(string $gameTitle, string $expectedSortTitle): void
    {
        // Act
        $sortTitle = (new ComputeGameSortTitleAction())->execute($gameTitle);

        // Assert
        $this->assertEquals($expectedSortTitle, $sortTitle);
    }

    /**
     * @return string[][]
     */
    public static function titleProvider(): array
    {
        return [
            '~Hack~ Dragoon X Omega' => ['~Hack~ Dragoon X Omega', '~hack dragoon x omega'],
            '~Hack~ Pokemon - X and Y' => ['~Hack~ Pokemon - X and Y', '~hack pokemon - x and y'],
            '~Hack~ V I T A L I T Y' => ['~Hack~ V I T A L I T Y', '~hack v i t a l i t y'],
            '~Homebrew~ Classic Kong' => ['~Homebrew~ Classic Kong', '~homebrew classic kong'],
            '007: Agent Under Fire' => ['007: Agent Under Fire', '00007 agent under fire'],
            '101 Dalmatians' => ['101 Dalmatians', '00101 dalmatians'],
            '50 Cent: Blood on the Sand' => ['50 Cent: Blood on the Sand', '00050 cent blood on the sand'],
            'A Series of Unfortunate Events' => ['A Series of Unfortunate Events', 'series of unfortunate events'],
            'American Tale, An' => ['American Tale, An', 'american tale'],
            'An Unexpected Journey' => ['An Unexpected Journey', 'unexpected journey'],
            'Bravely Défault II' => ['Bravely Défault II', 'bravely default 00002'],
            'Final Fantasy IV' => ['Final Fantasy IV', 'final fantasy 00004'],
            'Final Fantasy X' => ['Final Fantasy X', 'final fantasy 00010'],
            'Formula 1-97' => ['Formula 1-97', 'formula 00001-00097'],
            'GIVX' => ['GIVX', 'givx'],
            'Grand Day Out, A' => ['Grand Day Out, A', 'grand day out'],
            'HALF-LIFE 2' => ['HALF-LIFE 2', 'half-life 00002'],
            'I Have No Mouth, And I Must Scream' => ['I Have No Mouth, And I Must Scream', 'i have no mouth and i must scream'],
            'I\'m Sorry' => ["I'm Sorry", "im sorry"],
            'Kingdom Hearts HD 1.5 Remix' => ['Kingdom Hearts HD 1.5 Remix', 'kingdom hearts hd 0000100005 remix'],
            'Kingdom Hearts II Final Mix' => ['Kingdom Hearts II Final Mix', 'kingdom hearts ii final mix'],
            'Legend of Zelda, The: A Link to the Past' => ['Legend of Zelda, The: A Link to the Past', 'legend of zelda a link to the past'],
            'Luigi\'s Mansion' => ["Luigi's Mansion", "luigis mansion"],
            'Mega Man 10' => ['Mega Man 10', 'mega man 00010'],
            'Mega Man 2' => ['Mega Man 2', 'mega man 00002'],
            'Mega Man X4' => ['Mega Man X4', 'mega man x4'],
            'Mega Man ZX Advent' => ['Mega Man ZX Advent', 'mega man zx advent'],
            'Ōkami' => ['Ōkami', 'okami'],
            'Pokémon Stadium' => ['Pokémon Stadium', 'pokemon stadium'],
            'Puyo Puyo~n' => ['Puyo Puyo~n', 'puyo puyo~n'],
            'Shin Megami Tensei: Nocturne' => ['Shin Megami Tensei: Nocturne', 'shin megami tensei nocturne'],
            'Sonic the Hedgehog' => ['Sonic the Hedgehog', 'sonic the hedgehog'],
            'Star Wars Episode III: Revenge of the Sith' => ['Star Wars Episode III: Revenge of the Sith', 'star wars episode 00003 revenge of the sith'],
            'Super Mario 3D Land' => ['Super Mario 3D Land', 'super mario 3d land'],
            'Super Mario 64 DS' => ['Super Mario 64 DS', 'super mario 00064 ds'],
            'Super Mario Bros. 3' => ['Super Mario Bros. 3', 'super mario bros 00003'],
            'The Great Escape' => ['The Great Escape', 'great escape'],
            'The Matrix Reloaded' => ['The Matrix Reloaded', 'matrix reloaded'],
        ];
    }
}
