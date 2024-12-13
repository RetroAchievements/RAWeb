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

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Results' => [
                    [
                        "Rank" => 1,
                        'Score' => $leaderboardOneEntryOne->score,
                        'FormattedScore' => ValueFormat::format($leaderboardOneEntryOne->score, $leaderboardOne->Format),
                        'DateSubmitted' => $leaderboardOneEntryOne->created_at->toIso8601String(),
                        'LeaderboardID' => $leaderboardOne->ID,
                        'LeaderboardName' => $leaderboardOne->Title,
                        'LeaderboardDescription' => $leaderboardOne->Description,
                        'LeaderboardLowerIsBetter' => $leaderboardOne->LowerIsBetter,
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userFour->User]))
            ->assertSuccessful()
            ->assertJson([
                'Results' => [
                    [
                        "Rank" => 4,
                        'Score' => $leaderboardOneEntryFour->score,
                        'FormattedScore' => ValueFormat::format($leaderboardOneEntryFour->score, $leaderboardOne->Format),
                        'DateSubmitted' => $leaderboardOneEntryFour->created_at->toIso8601String(),
                        'LeaderboardID' => $leaderboardOne->ID,
                        'LeaderboardName' => $leaderboardOne->Title,
                        'LeaderboardDescription' => $leaderboardOne->Description,
                        'LeaderboardLowerIsBetter' => $leaderboardOne->LowerIsBetter,
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

        $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userOne->User]))
            ->assertSuccessful()
            ->assertJson([
                'Results' => [
                    [
                        "Rank" => 1,
                        'Score' => $leaderboardOneEntryOne->score,
                        'FormattedScore' => ValueFormat::format($leaderboardOneEntryOne->score, $leaderboardOne->Format),
                        'DateSubmitted' => $leaderboardOneEntryOne->created_at->toIso8601String(),
                        'LeaderboardID' => $leaderboardOne->ID,
                        'LeaderboardName' => $leaderboardOne->Title,
                        'LeaderboardDescription' => $leaderboardOne->Description,
                        'LeaderboardLowerIsBetter' => $leaderboardOne->LowerIsBetter,
                    ],
                    [
                        "Rank" => 3,
                        'Score' => $leaderboardTwoEntryOne->score,
                        'FormattedScore' => ValueFormat::format($leaderboardTwoEntryOne->score, $leaderboardTwo->Format),
                        'DateSubmitted' => $leaderboardTwoEntryOne->created_at->toIso8601String(),
                        'LeaderboardID' => $leaderboardTwo->ID,
                        'LeaderboardName' => $leaderboardTwo->Title,
                        'LeaderboardDescription' => $leaderboardTwo->Description,
                        'LeaderboardLowerIsBetter' => $leaderboardTwo->LowerIsBetter,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserGameLeaderboards', ['i' => $game->ID, 'u' => $userThree->User]))
            ->assertSuccessful()
            ->assertJson([
                'Results' => [
                    [
                        "Rank" => 3,
                        'Score' => $leaderboardOneEntryThree->score,
                        'FormattedScore' => ValueFormat::format($leaderboardOneEntryThree->score, $leaderboardOne->Format),
                        'DateSubmitted' => $leaderboardOneEntryThree->created_at->toIso8601String(),
                        'LeaderboardID' => $leaderboardOne->ID,
                        'LeaderboardName' => $leaderboardOne->Title,
                        'LeaderboardDescription' => $leaderboardOne->Description,
                        'LeaderboardLowerIsBetter' => $leaderboardOne->LowerIsBetter,
                    ],
                    [
                        "Rank" => 1,
                        'Score' => $leaderboardTwoEntryThree->score,
                        'FormattedScore' => ValueFormat::format($leaderboardTwoEntryThree->score, $leaderboardTwo->Format),
                        'DateSubmitted' => $leaderboardTwoEntryThree->created_at->toIso8601String(),
                        'LeaderboardID' => $leaderboardTwo->ID,
                        'LeaderboardName' => $leaderboardTwo->Title,
                        'LeaderboardDescription' => $leaderboardTwo->Description,
                        'LeaderboardLowerIsBetter' => $leaderboardTwo->LowerIsBetter,
                    ],
                ],
            ]);
    }
}
