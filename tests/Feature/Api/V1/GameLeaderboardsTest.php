<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
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
            'score' => 1,
        ]);
        $untrackedUser = User::factory()->create(['User' => 'cheater', "unranked_at" => Carbon::now(), "Untracked" => 1]);
        $untrackedLeaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $untrackedUser->ID,
            'score' => 2,
        ]);

        $untrackedUser2 = User::factory()->create(['User' => 'cheater2', "unranked_at" => Carbon::now(), "Untracked" => 1]);
        $untrackedLeaderboardEntry2 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $untrackedUser2->ID,
            'score' => 4,
        ]);

        $deletedEntryUser = User::factory()->create(['User' => 'deletedEntryUse']);
        $untrackedLeaderboardEntry2 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $deletedEntryUser->ID,
            'score' => 3,
            'deleted_at' => Carbon::now()->subDay(),
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
            'score' => 1,
        ]);
        $bannedUser = User::factory()->create(['User' => 'bannedUser', "banned_at" => Carbon::now()]);
        $bannedLeaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $bannedUser->ID,
            'score' => 2,
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
            'score' => 1,
        ]);
        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->ID,
            'user_id' => $userFour->ID,
            'score' => 2,
        ]);

        /** @var Leaderboard $leaderboardFour */
        $leaderboardFour = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 4",
            'Description' => "I am the fourth leaderboard",
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
            'score' => 2,
        ]);
        $userSix = User::factory()->create(['User' => 'myUser6']);
        $leaderboardEntrySix = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFive->ID,
            'user_id' => $userSix->ID,
            'score' => 1,
        ]);
        $userSeven = User::factory()->create(['User' => 'myUser7']);
        $leaderboardEntrySeven = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardFive->ID,
            'user_id' => $userSeven->ID,
            'score' => 3,
        ]);

        /** @var Leaderboard $hiddenLeaderboard */
        $hiddenLeaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test hidden leaderboard",
            'Description' => "I am a hidden leaderboard",
            'Format' => "TIME",
            'LowerIsBetter' => 1,
            'DisplayOrder' => -1,
        ]);
        $userEight = User::factory()->create(['User' => 'myUser8']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $hiddenLeaderboard->ID,
            'user_id' => $userEight->ID,
            'score' => 2,
        ]);

        // Force recalculation of denormalized data in leaderboards.
        $action = new RecalculateLeaderboardTopEntryAction();
        $action->execute($leaderboardOne);
        $action->execute($leaderboardTwo);
        $action->execute($leaderboardThree);
        $action->execute($leaderboardFour);
        $action->execute($leaderboardFive);

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
                            "ULID" => $leaderboardEntryOne->User->ulid,
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
                            "ULID" => $leaderboardEntryTwo->User->ulid,
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
                            "User" => $leaderboardEntryFour->User->User,
                            "ULID" => $leaderboardEntryFour->User->ulid,
                            "Score" => $leaderboardEntryFour->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntryFour->score, $leaderboardThree->Format),
                        ],
                    ],
                    [
                        "ID" => $leaderboardFour->ID,
                        "RankAsc" => boolval($leaderboardFour->LowerIsBetter),
                        "Title" => $leaderboardFour->Title,
                        "Description" => $leaderboardFour->Description,
                        "Format" => $leaderboardFour->Format,
                        "TopEntry" => [],
                    ],
                    [
                        "ID" => $leaderboardFive->ID,
                        "RankAsc" => boolval($leaderboardFive->LowerIsBetter),
                        "Title" => $leaderboardFive->Title,
                        "Description" => $leaderboardFive->Description,
                        "Format" => $leaderboardFive->Format,
                        "TopEntry" => [
                            "User" => $leaderboardEntrySix->User->User,
                            "ULID" => $leaderboardEntrySix->User->ulid,
                            "Score" => $leaderboardEntrySix->score,
                            "FormattedScore" => ValueFormat::format($leaderboardEntrySix->score, $leaderboardFive->Format),
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
                            "TopEntry" => [],
                        ],
                        [
                            "ID" => $leaderboardFive->ID,
                            "RankAsc" => boolval($leaderboardFive->LowerIsBetter),
                            "Title" => $leaderboardFive->Title,
                            "Description" => $leaderboardFive->Description,
                            "Format" => $leaderboardFive->Format,
                            "TopEntry" => [
                                "User" => $leaderboardEntrySix->User->User,
                                "ULID" => $leaderboardEntrySix->User->ulid,
                                "Score" => $leaderboardEntrySix->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntrySix->score, $leaderboardFive->Format),
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
                                "ULID" => $leaderboardEntryOne->User->ulid,
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
                                "ULID" => $leaderboardEntryTwo->User->ulid,
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
                                "ULID" => $leaderboardEntryTwo->User->ulid,
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
                                "User" => $leaderboardEntryFour->User->User,
                                "ULID" => $leaderboardEntryFour->User->ulid,
                                "Score" => $leaderboardEntryFour->score,
                                "FormattedScore" => ValueFormat::format($leaderboardEntryFour->score, $leaderboardThree->Format),
                            ],
                        ],
                    ],
                ]);
    }
}
