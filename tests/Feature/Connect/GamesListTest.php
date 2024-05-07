<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamesListTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testGamesList(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();
        /** @var System $system3 */
        $system3 = System::factory()->create();

        // games 1,2,3 have only published achievements
        // games 4,5,6 have only unpublished achievements
        // games 7,8,9 have no achievements
        // game 10 has published and unpublished achievements
        /** @var Game $game1 */
        $game1 = Game::factory()->create(['ConsoleID' => $system1->ID, 'ImageIcon' => '/Images/000001.png', 'achievements_published' => 3]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system2->ID, 'ImageIcon' => '/Images/000002.png', 'achievements_published' => 7]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system3->ID, 'ImageIcon' => '/Images/000003.png', 'achievements_published' => 11]);
        /** @var Game $game4 */
        $game4 = Game::factory()->create(['ConsoleID' => $system2->ID, 'ImageIcon' => '/Images/000004.png', 'achievements_unpublished' => 5]);
        /** @var Game $game5 */
        $game5 = Game::factory()->create(['ConsoleID' => $system1->ID, 'ImageIcon' => '/Images/000005.png', 'achievements_unpublished' => 9]);
        /** @var Game $game6 */
        $game6 = Game::factory()->create(['ConsoleID' => $system3->ID, 'ImageIcon' => '/Images/000006.png', 'achievements_unpublished' => 1]);
        /** @var Game $game7 */
        $game7 = Game::factory()->create(['ConsoleID' => $system1->ID, 'ImageIcon' => '/Images/000007.png']);
        /** @var Game $game8 */
        $game8 = Game::factory()->create(['ConsoleID' => $system2->ID, 'ImageIcon' => '/Images/000008.png']);
        /** @var Game $game9 */
        $game9 = Game::factory()->create(['ConsoleID' => $system3->ID, 'ImageIcon' => '/Images/000009.png']);
        /** @var Game $game10 */
        $game10 = Game::factory()->create(['ConsoleID' => $system2->ID, 'ImageIcon' => '/Images/000010.png', 'achievements_published' => 2, 'achievements_unpublished' => 1]);

        // all games for console 1
        $this->get($this->apiUrl('gameslist', ['c' => $system1->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '1' => $game1->Title,
                    '5' => $game5->Title,
                    '7' => $game7->Title,
                ],
            ]);

        // all games for console 2
        $this->get($this->apiUrl('gameslist', ['c' => $system2->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '2' => $game2->Title,
                    '4' => $game4->Title,
                    '8' => $game8->Title,
                    '10' => $game10->Title,
                ],
            ]);

        // all games for console 3
        $this->get($this->apiUrl('gameslist', ['c' => $system3->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    '3' => $game3->Title,
                    '6' => $game6->Title,
                    '9' => $game9->Title,
                ],
            ]);

    // games with published achievements for console 1
    $this->get($this->apiUrl('officialgameslist', ['c' => $system1->id]))
        ->assertExactJson([
            'Success' => true,
            'Response' => [
                '1' => $game1->Title,
            ],
        ]);

    // games with published achievements for console 2
    $this->get($this->apiUrl('officialgameslist', ['c' => $system2->id]))
        ->assertExactJson([
            'Success' => true,
            'Response' => [
                '2' => $game2->Title,
                '10' => $game10->Title,
            ],
        ]);

    // games with published achievements for console 3
    $this->get($this->apiUrl('officialgameslist', ['c' => $system3->id]))
        ->assertExactJson([
            'Success' => true,
            'Response' => [
                '3' => $game3->Title,
            ],
        ]);

    // game info
    $this->get($this->apiUrl('gameinfolist', ['g' => implode(',', [$game2->id, $game4->id, $game7->id])]))
        ->assertExactJson([
            'Success' => true,
            'Response' => [
                [
                    'ID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ImageIcon' => $game2->ImageIcon,
                ],
                [
                    'ID' => $game4->ID,
                    'Title' => $game4->Title,
                    'ImageIcon' => $game4->ImageIcon,
                ],
                [
                    'ID' => $game7->ID,
                    'Title' => $game7->Title,
                    'ImageIcon' => $game7->ImageIcon,
                ],
            ],
        ]);
    }
}
