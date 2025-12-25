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

        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
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

        Leaderboard::factory()->create(['game_id' => $game->id]);

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

        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);
        $leaderboardEntryOne->delete();

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
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

       $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

       LeaderboardEntry::factory()->create([
           'leaderboard_id' => $leaderboard->id,
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntry->score, $leaderboard->format),
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
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $user->ulid])) // !!
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntry->score, $leaderboard->format),
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
            'game_id' => $game->id,
            'title' => "Test leaderboard 1",
            'description' => "I am the first leaderboard",
            'rank_asc' => true,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 2",
            'description' => "I am the second leaderboard",
            'rank_asc' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->id,
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
                        'ID' => $leaderboardOne->id,
                        'RankAsc' => $leaderboardOne->rank_asc,
                        'Title' => $leaderboardOne->title,
                        'Description' => $leaderboardOne->description,
                        'Format' => $leaderboardOne->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardOneEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntry->score, $leaderboardOne->format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntry->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->id,
                        'RankAsc' => $leaderboardTwo->rank_asc,
                        'Title' => $leaderboardTwo->title,
                        'Description' => $leaderboardTwo->description,
                        'Format' => $leaderboardTwo->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->format),
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
            'game_id' => $game->id,
            'title' => "Test leaderboard 1",
            'description' => "I am the first leaderboard",
            'rank_asc' => true,
        ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 2",
            'description' => "I am the second leaderboard",
            'rank_asc' => true,
        ]);

        /** @var Leaderboard $leaderboardThree */
        $leaderboardThree = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard 3",
            'description' => "I am the third leaderboard",
            'rank_asc' => true,
        ]);

        $user = User::factory()->create(['User' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->id,
            'user_id' => $user->ID,
            'score' => 1,
        ]);

        $leaderboardThreeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->id,
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
                        'ID' => $leaderboardOne->id,
                        'RankAsc' => $leaderboardOne->rank_asc,
                        'Title' => $leaderboardOne->title,
                        'Description' => $leaderboardOne->description,
                        'Format' => $leaderboardOne->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardOneEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntry->score, $leaderboardOne->format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntry->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->id,
                        'RankAsc' => $leaderboardTwo->rank_asc,
                        'Title' => $leaderboardTwo->title,
                        'Description' => $leaderboardTwo->description,
                        'Format' => $leaderboardTwo->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->format),
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
                        'ID' => $leaderboardTwo->id,
                        'RankAsc' => $leaderboardTwo->rank_asc,
                        'Title' => $leaderboardTwo->title,
                        'Description' => $leaderboardTwo->description,
                        'Format' => $leaderboardTwo->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->format),
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
                        'ID' => $leaderboardThree->id,
                        'RankAsc' => $leaderboardThree->rank_asc,
                        'Title' => $leaderboardThree->title,
                        'Description' => $leaderboardThree->description,
                        'Format' => $leaderboardThree->format,
                        'UserEntry' => [
                            'User' => $user->User,
                            'Score' => $leaderboardThreeEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardThreeEntry->score, $leaderboardThree->format),
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => false,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2', 'unranked_at' => Carbon::now(), 'Untracked' => 1]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userThree->User,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->format),
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userTwo->ID,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userThree->ID,
            'score' => 200,
        ]);

        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userFour->ID,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['User' => 'myUser5']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userThree->User,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userFour->User,
                            'Score' => $leaderboardEntryFour->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFour->score, $leaderboard->format),
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
            'game_id' => $game->id,
            'title' => "Active leaderboard",
            'description' => "I am active",
            'rank_asc' => true,
        ]);

        /** @var Leaderboard $deletedLeaderboard */
        $deletedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Deleted leaderboard",
            'description' => "I am deleted",
            'rank_asc' => true,
            'deleted_at' => now(),
        ]);

        $user = User::factory()->create(['User' => 'testUser']);

        // ... create entries for both leaderboards ...
        $activeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $activeLeaderboard->id,
            'user_id' => $user->ID,
            'score' => 100,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $deletedLeaderboard->id,
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
                        'ID' => $activeLeaderboard->id,
                        'RankAsc' => $activeLeaderboard->rank_asc,
                        'Title' => $activeLeaderboard->title,
                        'Description' => $activeLeaderboard->description,
                        'Format' => $activeLeaderboard->format,
                        'UserEntry' => [ // !! just one entry, for the non-deleted leaderboard
                            'User' => $user->User,
                            'Score' => $activeEntry->score,
                            'FormattedScore' => ValueFormat::format($activeEntry->score, $activeLeaderboard->format),
                            'Rank' => 1,
                            'DateUpdated' => $activeEntry->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }
}
