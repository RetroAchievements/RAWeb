<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
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
            'Format' => "TIME",
            'LowerIsBetter' => 1,
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
                        "RankAsc" => boolval($leaderboardOne->LowerIsBetter),
                        "Title" => $leaderboardOne->Title,
                        "Description" => $leaderboardOne->Description,
                        "Format" => $leaderboardOne->Format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryOne->User->User,
                            "Score" => $leaderboardEntryOne->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryOne->score, $leaderboardOne->Format),
                        ],
                    ],
                    [
                        "ID" => $leaderboardTwo->ID,
                        "RankAsc" => boolval($leaderboardTwo->LowerIsBetter),
                        "Title" => $leaderboardTwo->Title,
                        "Description" => $leaderboardTwo->Description,
                        "Format" => $leaderboardTwo->Format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryTwo->User->User,
                            "Score" => $leaderboardEntryTwo->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryTwo->score, $leaderboardTwo->Format),
                        ],
                    ],
                    [
                        "ID" => $leaderboardThree->ID,
                        "RankAsc" => boolval($leaderboardThree->LowerIsBetter),
                        "Title" => $leaderboardThree->Title,
                        "Description" => $leaderboardThree->Description,
                        "Format" => $leaderboardThree->Format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryThree->User->User,
                            "Score" => $leaderboardEntryThree->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryThree->score, $leaderboardThree->Format),
                        ],
                    ],
                    [
                        "ID" => $leaderboardFour->ID,
                        "RankAsc" => boolval($leaderboardFour->LowerIsBetter),
                        "Title" => $leaderboardFour->Title,
                        "Description" => $leaderboardFour->Description,
                        "Format" => $leaderboardFour->Format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryFour->User->User,
                            "Score" => $leaderboardEntryFour->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryFour->score, $leaderboardFour->Format),
                        ],
                    ],
                    [
                        "ID" => $leaderboardFive->ID,
                        "RankAsc" => boolval($leaderboardFive->LowerIsBetter),
                        "Title" => $leaderboardFive->Title,
                        "Description" => $leaderboardFive->Description,
                        "Format" => $leaderboardFive->Format,
                        "TopEntry" => [
                            "User" => $leaderboardEntryFive->User->User,
                            "Score" => $leaderboardEntryFive->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryFive->score, $leaderboardFive->Format),
                        ],
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
                            "RankAsc" => boolval($leaderboardFour->LowerIsBetter),
                            "Title" => $leaderboardFour->Title,
                            "Description" => $leaderboardFour->Description,
                            "Format" => $leaderboardFour->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryFour->User->User,
                                "Score" => $leaderboardEntryFour->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryFour->score, $leaderboardFour->Format),
                            ],
                        ],
                        [
                            "ID" => $leaderboardFive->ID,
                            "RankAsc" => boolval($leaderboardFive->LowerIsBetter),
                            "Title" => $leaderboardFive->Title,
                            "Description" => $leaderboardFive->Description,
                            "Format" => $leaderboardFive->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryFive->User->User,
                                "Score" => $leaderboardEntryFive->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryFive->score, $leaderboardFive->Format),
                            ],
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
                            "RankAsc" => boolval($leaderboardOne->LowerIsBetter),
                            "Title" => $leaderboardOne->Title,
                            "Description" => $leaderboardOne->Description,
                            "Format" => $leaderboardOne->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryOne->User->User,
                                "Score" => $leaderboardEntryOne->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryOne->score, $leaderboardOne->Format),
                            ],
                        ],
                        [
                            "ID" => $leaderboardTwo->ID,
                            "RankAsc" => boolval($leaderboardTwo->LowerIsBetter),
                            "Title" => $leaderboardTwo->Title,
                            "Description" => $leaderboardTwo->Description,
                            "Format" => $leaderboardTwo->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryTwo->User->User,
                                "Score" => $leaderboardEntryTwo->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryTwo->score, $leaderboardTwo->Format),
                            ],
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
                            "RankAsc" => boolval($leaderboardTwo->LowerIsBetter),
                            "Title" => $leaderboardTwo->Title,
                            "Description" => $leaderboardTwo->Description,
                            "Format" => $leaderboardTwo->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryTwo->User->User,
                                "Score" => $leaderboardEntryTwo->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryTwo->score, $leaderboardTwo->Format),
                            ],
                        ],
                        [
                            "ID" => $leaderboardThree->ID,
                            "RankAsc" => boolval($leaderboardThree->LowerIsBetter),
                            "Title" => $leaderboardThree->Title,
                            "Description" => $leaderboardThree->Description,
                            "Format" => $leaderboardThree->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntryThree->User->User,
                                "Score" => $leaderboardEntryThree->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryThree->score, $leaderboardThree->Format),
                            ],
                        ],
                    ],
                ]);
    }
}
