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

class LeaderboardSubmissionsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetLeaderboardSubmissions'))
            ->assertJsonValidationErrors([
                'i',
            ]);
    }

    public function testGetLeaderboardSubmissionsLeaderboardWithNoSubmissions(): void
    {
        $this->get($this->apiUrl('GetLeaderboardSubmissions', ['i' => 99999]))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetLeaderboardSubmissions(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a leaderboard with 5 submissions: */

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->ID,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->ID,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->ID,
        ]);

        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFour->ID,
        ]);

        $userFive = User::factory()->create(['User' => 'myUser5']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFive->ID,
        ]);

        $this->get($this->apiUrl('GetLeaderboardSubmissions', ['i' => $leaderboard->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Rank" => $leaderboardEntryOne->id,
                        'User' => $userOne->User,
                        'Score' => $leaderboardEntryOne->score,
                        'DateSubmitted' => $leaderboardEntryOne->updated_at->toDateTimeString(),
                    ],
                    [
                        "Rank" => $leaderboardEntryTwo->id,
                        'User' => $userTwo->User,
                        'Score' => $leaderboardEntryTwo->score,
                        'DateSubmitted' => $leaderboardEntryTwo->updated_at->toDateTimeString(),
                    ],
                    [
                        "Rank" => $leaderboardEntryThree->id,
                        'User' => $userThree->User,
                        'Score' => $leaderboardEntryThree->score,
                        'DateSubmitted' => $leaderboardEntryThree->updated_at->toDateTimeString(),
                    ],
                    [
                        "Rank" => $leaderboardEntryFour->id,
                        'User' => $userFour->User,
                        'Score' => $leaderboardEntryFour->score,
                        'DateSubmitted' => $leaderboardEntryFour->updated_at->toDateTimeString(),
                    ],
                    [
                        "Rank" => $leaderboardEntryFive->id,
                        'User' => $userFive->User,
                        'Score' => $leaderboardEntryFive->score,
                        'DateSubmitted' => $leaderboardEntryFive->updated_at->toDateTimeString(),
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetLeaderboardSubmissions', ['i' => $leaderboard->ID, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => $leaderboardEntryFour->id,
                            'User' => $userFour->User,
                            'Score' => $leaderboardEntryFour->score,
                            'DateSubmitted' => $leaderboardEntryFour->updated_at->toDateTimeString(),
                        ],
                        [
                            "Rank" => $leaderboardEntryFive->id,
                            'User' => $userFive->User,
                            'Score' => $leaderboardEntryFive->score,
                            'DateSubmitted' => $leaderboardEntryFive->updated_at->toDateTimeString(),
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetLeaderboardSubmissions', ['i' => $leaderboard->ID, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => $leaderboardEntryOne->id,
                            'User' => $userOne->User,
                            'Score' => $leaderboardEntryOne->score,
                            'DateSubmitted' => $leaderboardEntryOne->updated_at->toDateTimeString(),
                        ],
                        [
                            "Rank" => $leaderboardEntryTwo->id,
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'DateSubmitted' => $leaderboardEntryTwo->updated_at->toDateTimeString(),
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetLeaderboardSubmissions', ['i' => $leaderboard->ID, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => $leaderboardEntryTwo->id,
                            'User' => $userTwo->User,
                            'Score' => $leaderboardEntryTwo->score,
                            'DateSubmitted' => $leaderboardEntryTwo->updated_at->toDateTimeString(),
                        ],
                        [
                            "Rank" => $leaderboardEntryThree->id,
                            'User' => $userThree->User,
                            'Score' => $leaderboardEntryThree->score,
                            'DateSubmitted' => $leaderboardEntryThree->updated_at->toDateTimeString(),
                        ],
                    ],
                ]);
    }
}
