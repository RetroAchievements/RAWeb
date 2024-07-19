<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
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
        $this->get($this->apiUrl('GetGameLeaderboards', ['i' => 99999]))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetGameLeaderboards(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a game with 5 leaderboards: */

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboardOne */
        $leaderboardOne = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
        ]);
        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $userOne->ID,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
        ]);
        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $userTwo->ID,
        ]);

        /** @var Leaderboard $leaderboardThree */
        $leaderboardThree = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 3",
            'Description' => "I am the third leaderboard",
        ]);
        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->ID,
            'user_id' => $userThree->ID,
        ]);

        /** @var Leaderboard $leaderboardFour */
        $leaderboardFour = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 4",
            'Description' => "I am the fourth leaderboard",
        ]);
        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFour->ID,
            'user_id' => $userFour->ID,
        ]);

        /** @var Leaderboard $leaderboardFive */
        $leaderboardFive = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 5",
            'Description' => "I am the fifth leaderboard",
        ]);
        $userFive = User::factory()->create(['User' => 'myUser5']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFive->ID,
            'user_id' => $userFive->ID,
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
                        "CurrentLeader" => $userOne->User,
                    ],
                    [
                        "ID" => $leaderboardTwo->ID,
                        "Title" => $leaderboardTwo->Title,
                        "Description" => $leaderboardTwo->Description,
                        "CurrentLeader" => $userTwo->User,
                    ],
                    [
                        "ID" => $leaderboardThree->ID,
                        "Title" => $leaderboardThree->Title,
                        "Description" => $leaderboardThree->Description,
                        "CurrentLeader" => $userThree->User,
                    ],
                    [
                        "ID" => $leaderboardFour->ID,
                        "Title" => $leaderboardFour->Title,
                        "Description" => $leaderboardFour->Description,
                        "CurrentLeader" => $userFour->User,
                    ],
                    [
                        "ID" => $leaderboardFive->ID,
                        "Title" => $leaderboardFive->Title,
                        "Description" => $leaderboardFive->Description,
                        "CurrentLeader" => $userFive->User,
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
