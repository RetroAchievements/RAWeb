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

        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
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

        Leaderboard::factory()->create(['game_id' => $game->id]);

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

        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);
        $leaderboardEntryOne->delete();

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->username,
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
       $game = Game::factory()->create(['system_id' => $system->id]);

       $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

       LeaderboardEntry::factory()->create([
           'leaderboard_id' => $leaderboard->id,
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
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

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

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->id,
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
                        'ID' => $leaderboardOne->id,
                        'RankAsc' => $leaderboardOne->rank_asc,
                        'Title' => $leaderboardOne->title,
                        'Description' => $leaderboardOne->description,
                        'Format' => $leaderboardOne->format,
                        'UserEntry' => [
                            'User' => $user->username,
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
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

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

        $user = User::factory()->create(['username' => 'myUser1']);
        $leaderboardOneEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->id,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $leaderboardTwoEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->id,
            'user_id' => $user->id,
            'score' => 1,
        ]);

        $leaderboardThreeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->id,
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
                        'ID' => $leaderboardOne->id,
                        'RankAsc' => $leaderboardOne->rank_asc,
                        'Title' => $leaderboardOne->title,
                        'Description' => $leaderboardOne->description,
                        'Format' => $leaderboardOne->format,
                        'UserEntry' => [
                            'User' => $user->username,
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
                            'User' => $user->username,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->format),
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
                        'ID' => $leaderboardTwo->id,
                        'RankAsc' => $leaderboardTwo->rank_asc,
                        'Title' => $leaderboardTwo->title,
                        'Description' => $leaderboardTwo->description,
                        'Format' => $leaderboardTwo->format,
                        'UserEntry' => [
                            'User' => $user->username,
                            'Score' => $leaderboardTwoEntry->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntry->score, $leaderboardTwo->format),
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
                        'ID' => $leaderboardThree->id,
                        'RankAsc' => $leaderboardThree->rank_asc,
                        'Title' => $leaderboardThree->title,
                        'Description' => $leaderboardThree->description,
                        'Format' => $leaderboardThree->format,
                        'UserEntry' => [
                            'User' => $user->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userOne->username,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => false,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userOne->username,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2', 'unranked_at' => Carbon::now(), 'Untracked' => 1]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['username' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userThree->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => "Test leaderboard",
            'description' => "I am a leaderboard",
            'rank_asc' => true,
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->id,
            'score' => 100,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userTwo->id,
            'score' => 200,
        ]);

        $userThree = User::factory()->create(['username' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userThree->id,
            'score' => 200,
        ]);

        $userFour = User::factory()->create(['username' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userFour->id,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['username' => 'myUser5']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userTwo->username,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userThree->username,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->format),
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
                        'ID' => $leaderboard->id,
                        'RankAsc' => $leaderboard->rank_asc,
                        'Title' => $leaderboard->title,
                        'Description' => $leaderboard->description,
                        'Format' => $leaderboard->format,
                        'UserEntry' => [
                            'User' => $userFour->username,
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
        $game = Game::factory()->create(['system_id' => $system->id]);

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

        $user = User::factory()->create(['username' => 'testUser']);

        // ... create entries for both leaderboards ...
        $activeEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $activeLeaderboard->id,
            'user_id' => $user->id,
            'score' => 100,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $deletedLeaderboard->id,
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
                        'ID' => $activeLeaderboard->id,
                        'RankAsc' => $activeLeaderboard->rank_asc,
                        'Title' => $activeLeaderboard->title,
                        'Description' => $activeLeaderboard->description,
                        'Format' => $activeLeaderboard->format,
                        'UserEntry' => [ // !! just one entry, for the non-deleted leaderboard
                            'User' => $user->username,
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
