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

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => 99999, 'u' => $user->username]))
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);
        $leaderboard->delete();

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        Leaderboard::factory()->create(['GameID' => $game->id]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);

        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);
        $leaderboardEntry->delete();

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
            ->assertUnprocessable()
            ->assertJson(['User has no leaderboards on this game']);
    }

    public function testGetUserGameLeaderboardsNotComparedAgainstDeletedEntries(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);
        $leaderboardEntryOne->delete();

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userTwo->username]))
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
                            'User' => $userTwo->username,
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
       $game = Game::factory()->create(['system_id' => $system->id]);

       $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);

       LeaderboardEntry::factory()->create([
           'leaderboard_id' => $leaderboard->ID,
           'user_id' => $user->id,
           'score' => 1,
       ]);

       $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
           ->assertUnprocessable()
           ->assertJson(['User has no leaderboards on this game']);
    }

    public function testGetUserGameLeaderboardsHavingASingleLeaderboardEntryForGame(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
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
                            'User' => $user->username,
                            'Score' => $leaderboardEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntry->score, $leaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserGameLeaderboardsHavingASingleLeaderboardEntryForGameByUlid(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->ulid])) // !!
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
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboardOne */
        $leaderboardOne = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
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
                            'User' => $user->username,
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
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboardOne */
        $leaderboardOne = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $leaderboardThree */
        $leaderboardThree = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 3",
            'Description' => "I am the third leaderboard",
            'LowerIsBetter' => true,
        ]);

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $leaderboardThreeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->ID,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username, 'o' => 0, 'c' => 2]))
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
                            'User' => $user->username,
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
                            'User' => $user->username,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardTwoEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username, 'o' => 1, 'c' => 1]))
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
                            'User' => $user->username,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardTwoEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username, 'o' => 2, 'c' => 1]))
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
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userOne->username]))
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
                            'User' => $userOne->username,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userTwo->username]))
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
                            'User' => $userTwo->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => false,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userOne->username]))
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
                            'User' => $userOne->username,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userTwo->username]))
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
                            'User' => $userTwo->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2', 'unranked_at' => Carbon::now(), 'Untracked' => 1]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['username' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->id,
            'score' => 300,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userThree->username]))
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
                            'User' => $userThree->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard",
            'Description' => "I am a leaderboard",
            'LowerIsBetter' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['username' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->id,
            'score' => 200,
        ]);

        $userFour = User::factory()->create(['username' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFour->id,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['username' => 'myUser5']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFive->id,
            'score' => 400,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userTwo->username]))
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
                            'User' => $userTwo->username,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryTwo->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userThree->username]))
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
                            'User' => $userThree->username,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->Format),
                            'Rank' => 2,
                            'DateUpdated' => $leaderboardEntryThree->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $userFour->username]))
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
                            'User' => $userFour->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $activeLeaderboard */
        $activeLeaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Active leaderboard",
            'Description' => "I am active",
            'LowerIsBetter' => true,
        ]);

        /** @var Leaderboard $deletedLeaderboard */
        $deletedLeaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Deleted leaderboard",
            'Description' => "I am deleted",
            'LowerIsBetter' => true,
            'deleted_at' => now(),
        ]);

        $user = User::factory()->create(['username' => 'testUser']);

        // ... create entries for both leaderboards ...
        $activeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $activeLeaderboard->ID,
            'user_id' => $user->id,
            'score' => 100,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $deletedLeaderboard->ID,
            'user_id' => $user->id,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->id, 'u' => $user->username]))
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
                            'User' => $user->username,
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
