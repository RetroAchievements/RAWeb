<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Leaderboard;
use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GameLeaderboardsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetGameLeaderboards'))
            ->assertJsonValidationErrors([
                'i',
            ]);
    }

    public function testGetGameLeaderboardsGameWithNoLeaderboards(): void
    {
        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetGameLeaderboards(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a game with 5 leaderboards: */

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $gameOne */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $leaderboardOne = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
        ]);
        $leaderboardTwo =Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
        ]);
        $leaderboardThree =Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 3",
            'Description' => "I am the third leaderboard",
        ]);
        $leaderboardFour =Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 4",
            'Description' => "I am the fourth leaderboard",
        ]);
        $leaderboardFive =Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 5",
            'Description' => "I am the fifth leaderboard",
        ]);


        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "ID" => $leaderboardOne->ID,
                        "Title" => $leaderboardOne->Title,
                        "Description" => $leaderboardOne->Description,
                    ],
                    [
                        "ID" => $leaderboardTwo->ID,
                        "Title" => $leaderboardTwo->Title,
                        "Description" => $leaderboardTwo->Description,
                    ],
                    [
                        "ID" => $leaderboardThree->ID,
                        "Title" => $leaderboardThree->Title,
                        "Description" => $leaderboardThree->Description,
                    ],
                    [
                        "ID" => $leaderboardFour->ID,
                        "Title" => $leaderboardFour->Title,
                        "Description" => $leaderboardFour->Description,
                    ],
                    [
                        "ID" => $leaderboardFive->ID,
                        "Title" => $leaderboardFive->Title,
                        "Description" => $leaderboardFive->Description,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $leaderboardFour->ID,
                            "Title" => $leaderboardFour->Title,
                            "Description" => $leaderboardFour->Description,
                        ],
                        [
                            "ID" => $leaderboardFive->ID,
                            "Title" => $leaderboardFive->Title,
                            "Description" => $leaderboardFive->Description,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $leaderboardOne->ID,
                            "Title" => $leaderboardOne->Title,
                            "Description" => $leaderboardOne->Description,
                        ],
                        [
                            "ID" => $leaderboardTwo->ID,
                            "Title" => $leaderboardTwo->Title,
                            "Description" => $leaderboardTwo->Description,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetGameLeaderboards', ['i' => $game->ID, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $leaderboardTwo->ID,
                            "Title" => $leaderboardTwo->Title,
                            "Description" => $leaderboardTwo->Description,
                        ],
                        [
                            "ID" => $leaderboardThree->ID,
                            "Title" => $leaderboardThree->Title,
                            "Description" => $leaderboardThree->Description,
                        ],
                    ],
                ]);
    }
}