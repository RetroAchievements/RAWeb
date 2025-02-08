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

class GetUserGameLeaderboardsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserGameLeaderboards'))
            ->assertJsonValidationErrors([
                'i',
                'u',
            ]);
    }

    public function testGetUserGameLeaderboardsUserNotFound(): void
    {
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => 99999, 'u' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson(['User not found']);
    }

    public function testGetUserGameLeaderboardsGameNotFound(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => 99999, 'u' => $user->User]))
            ->assertNotFound()
            ->assertJson(['Game not found']);
    }

    public function testGetUserGameLeaderboardsGameHasNoLeaderboards(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertUnprocessable()
            ->assertJson(['Game has no leaderboards']);
    }

    public function testGetUserGameLeaderboardsGameHasLeaderboardsDeleted(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->ID]);
        $leaderboard->delete();

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertUnprocessable()
            ->assertJson(['Game has no leaderboards']);
    }

    public function testGetUserGameLeaderboardsUserHasNoLeaderboardsOnGame(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        Leaderboard::factory()->create(['GameID' => $game->ID]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertUnprocessable()
            ->assertJson(['User has no leaderboards on this game']);
    }

    public function testGetUserGameLeaderboardsUserHasLeaderboardsOnGameDeleted(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->ID]);

        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);
        $leaderboardEntry->delete();

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertUnprocessable()
            ->assertJson(['User has no leaderboards on this game']);
    }

    public function testGetUserGameLeaderboardsNotComparedAgainstDeletedEntries(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);
        $leaderboardEntryOne->delete();

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userTwo->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardEntryTwo->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

    }

    public function testGetUserGameLeaderboardsUserIsUnRanked(): void
    {
       /** @var User $user */
       $user = User::factory()->create(['unranked_at' => Carbon::now(), 'Untracked' => 1]);

       /** @var System $system */
       $system = System::factory()->create();

       /** @var Game $game */
       $game = Game::factory()->create(['ConsoleID' => $system->ID]);

       $leaderboard = Leaderboard::factory()->create(['GameID' => $game->ID]);

       LeaderboardEntry::factory()->create([
           'leaderboard_id' => $leaderboard->ID,
           'user_id' => $user->ID,
           'score' => 1,
       ]);

       $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
           ->assertUnprocessable()
           ->assertJson(['User has no leaderboards on this game']);
    }

    public function testGetUserGameLeaderboardsHavingASingleLeaderboardEntryForGame(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntry->score, $leaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsHavingMultipleLeaderboardEntryForGame(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboardOne */
        $leaderboardOne = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 2,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter,
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardOneEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntry->score, $leaderboardOne->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntry->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter,
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardTwoEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardPagination(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboardOne */
        $leaderboardOne = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $leaderboardThree */
        $leaderboardThree = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 3",
            'Description' => "I am the third leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $leaderboardThreeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->ID,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User, 'o' => 0, 'c' => 2]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter,
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardOneEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntry->score, $leaderboardOne->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntry->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter,
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardTwoEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User, 'o' => 1, 'c' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter,
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardTwoEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User, 'o' => 2, 'c' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $leaderboardThree->ID,
                        'RankAsc' => $leaderboardThree->LowerIsBetter,
                        'Title' => $leaderboardThree->Title,
                        'Description' => $leaderboardThree->Description,
                        'Format' => $leaderboardThree->Format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardThreeEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardThreeEntry->score, $leaderboardThree->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardThreeEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsRankCalculationWhenLeaderboardIsLowerIsBetter(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userTwo->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryTwo->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsRankCalculationWhenLeaderboardIsLowerIsNotBetter(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => false,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userTwo->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardEntryTwo->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsRankCalculationWhenThereIsUnRankedUser(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2', 'unranked_at' => Carbon::now(), 'Untracked' => 1]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->ID,
            'score' => 300,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userThree->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userThree->User,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryThree->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsRankCalculationWhenTie(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->ID,
            'score' => 200,
        ]);

        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFour->ID,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['User' => 'myUser5']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFive->ID,
            'score' => 400,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userTwo->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryTwo->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userThree->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userThree->User,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryThree->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userFour->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->ID,
                        'RankAsc' => $leaderboard->LowerIsBetter,
                        'Title' => $leaderboard->Title,
                        'Description' => $leaderboard->Description,
                        'Format' => $leaderboard->Format,
                        'UserEntry' => [
                            'User' => $userFour->User,
                            'Score' => $leaderboardEntryFour->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFour->score, $leaderboard->Format),
                            'Rank' => 4,
                            'DateUpdated' => $leaderboardEntryFour->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsDoesNotIncludeDeletedLeaderboards(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $activeLeaderboard */
        $activeLeaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Active leaderboard",
            'Description' => "I am active",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $deletedLeaderboard */
        $deletedLeaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Deleted leaderboard",
            'Description' => "I am deleted",
            'LowerIsBetter' => true,
            'deleted_at' => now(),
        ]);

        $user = User::factory()->create(['User' => 'testUser']);

        // ... create entries for both leaderboards ...
        $activeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $activeLeaderboard->ID,
            'user_id' => $user->ID,
            'score' => 100,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $deletedLeaderboard->ID,
            'user_id' => $user->ID,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1, // !! only count the non-deleted leaderboard
                'Results' => [
                    [
                        'ID' => $activeLeaderboard->ID,
                        'RankAsc' => $activeLeaderboard->LowerIsBetter,
                        'Title' => $activeLeaderboard->Title,
                        'Description' => $activeLeaderboard->Description,
                        'Format' => $activeLeaderboard->Format,
                        'UserEntry' => [ // !! just one entry, for the non-deleted leaderboard
                            'User' => $user->User,
                            'Score' => $activeEntry->score,
                            'FormattedScore' => ValueFormat::format($activeEntry->score, $activeLeaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $activeEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }
}
