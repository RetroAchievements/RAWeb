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

    public function testGetUserGameLeaderboards(): void
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

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardOneEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $userOne->ID,
            'score' => 1,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $userTwo->ID,
            'score' => 1,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardOneEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $userThree->ID,
            'score' => 50,
        ]);

        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardOneEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardOne->ID,
            'user_id' => $userFour->ID,
            'score' => 300,
        ]);

        // User 1 has 1 leaderboard entry where he is ranked 1st
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardOneEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntryOne->score, $leaderboardOne->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        // User 4 has 1 leaderboard entry where he is ranked 4th
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userFour->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $userFour->User,
                            'Score' => $leaderboardOneEntryFour->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntryFour->score, $leaderboardOne->Format),
                            'Rank' => 4,
                            'DateUpdated' => $leaderboardOneEntryFour->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        /** @var Leaderboard $leaderboardTwo */
        $leaderboardTwo = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 2",
            'Description' => "I am the second leaderboard",
            'LowerIsBetter' => true,
        ]);

        $leaderboardTwoEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $userOne->ID,
            'score' => 1000,
        ]);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $userTwo->ID,
            'score' => 100,
        ]);

        $leaderboardTwoEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardTwo->ID,
            'user_id' => $userThree->ID,
            'score' => 10,
        ]);

        // User 1 has 2 leaderboard entries where he is ranked 1st and 3rd
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 2,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardOneEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntryOne->score, $leaderboardOne->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardTwoEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntryOne->score, $leaderboardTwo->Format),
                            'Rank' => 3,
                            'DateUpdated' => $leaderboardTwoEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        // User 3 has 2 leaderboard entries where he is ranked 3rd and 1st
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userThree->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 2,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $userThree->User,
                            'Score' => $leaderboardOneEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntryThree->score, $leaderboardOne->Format),
                            'Rank' => 3,
                            'DateUpdated' => $leaderboardOneEntryThree->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $userThree->User,
                            'Score' => $leaderboardTwoEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntryThree->score, $leaderboardTwo->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardTwoEntryThree->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        /** @var Leaderboard $leaderboardThree */
        $leaderboardThree = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 3",
            'Description' => "I am the third leaderboard",
            'LowerIsBetter' => true,
        ]);

        $leaderboardThreeEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboardThree->ID,
            'user_id' => $userOne->ID,
            'score' => 1000,
        ]);

        // User 1 has 3 leaderboard entries where he is ranked 1st, 3rd, and 1st
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 3,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardOneEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntryOne->score, $leaderboardOne->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardTwoEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntryOne->score, $leaderboardTwo->Format),
                            'Rank' => 3,
                            'DateUpdated' => $leaderboardTwoEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardThree->ID,
                        'RankAsc' => $leaderboardThree->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardThree->Title,
                        'Description' => $leaderboardThree->Description,
                        'Format' => $leaderboardThree->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardThreeEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardThreeEntryOne->score, $leaderboardThree->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardThreeEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        // User 1 has 3 leaderboard entries where he is ranked 1st, 3rd, and 1st, but we only want the first 2 results
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User, 'c' => 2, 'o' => 0]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $leaderboardOne->ID,
                        'RankAsc' => $leaderboardOne->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardOne->Title,
                        'Description' => $leaderboardOne->Description,
                        'Format' => $leaderboardOne->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardOneEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardOneEntryOne->score, $leaderboardOne->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardOneEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                    [
                        'ID' => $leaderboardTwo->ID,
                        'RankAsc' => $leaderboardTwo->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardTwo->Title,
                        'Description' => $leaderboardTwo->Description,
                        'Format' => $leaderboardTwo->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardTwoEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardTwoEntryOne->score, $leaderboardTwo->Format),
                            'Rank' => 3,
                            'DateUpdated' => $leaderboardTwoEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        // User 1 has 3 leaderboard entries where he is ranked 1st, 3rd, and 1st, but we only expect the last result even though we're asking for 2 results
        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User, 'o' => 2, 'c' => 2]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 3,
                'Results' => [
                    [
                        'ID' => $leaderboardThree->ID,
                        'RankAsc' => $leaderboardThree->LowerIsBetter ? 'false' : 'true',
                        'Title' => $leaderboardThree->Title,
                        'Description' => $leaderboardThree->Description,
                        'Format' => $leaderboardThree->Format,
                        'UserEntry' => [
                            'User' => $userOne->User,
                            'Score' => $leaderboardThreeEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardThreeEntryOne->score, $leaderboardThree->Format),
                            'Rank' => 1,
                            'DateUpdated' => $leaderboardThreeEntryOne->created_at->toIso8601String(),
                        ],
                    ],
                ],
            ]);
    }
}
