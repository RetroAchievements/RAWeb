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

class LeaderboardEntriesTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetLeaderboardEntries'))
            ->assertJsonValidationErrors([
                'i',
            ]);
    }

    public function testGetLeaderboardEntriesLeaderboardWithNoEntries(): void
    {
        $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => 99999]))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetLeaderboardEntries(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a leaderboard with 5 entries: */

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
            'score' => 1,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->ID,
            'score' => 1,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->ID,
            'score' => 100,
        ]);

        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFour->ID,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['User' => 'myUser5']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFive->ID,
            'score' => 200,
        ]);

        $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Rank" => $leaderboard->getRank($leaderboardEntryFour->score),
                        'User' => $userFour->User,
                        'FormattedScore' => $leaderboardEntryFour->score,
                        'DateSubmitted' => $leaderboardEntryFour->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => $leaderboard->getRank($leaderboardEntryFive->score),
                        'User' => $userFive->User,
                        'FormattedScore' => $leaderboardEntryFive->score,
                        'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => $leaderboard->getRank($leaderboardEntryThree->score),
                        'User' => $userThree->User,
                        'FormattedScore' => $leaderboardEntryThree->score,
                        'DateSubmitted' => $leaderboardEntryThree->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => $leaderboard->getRank($leaderboardEntryOne->score),
                        'User' => $userOne->User,
                        'FormattedScore' => $leaderboardEntryOne->score,
                        'DateSubmitted' => $leaderboardEntryOne->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => $leaderboard->getRank($leaderboardEntryTwo->score),
                        'User' => $userTwo->User,
                        'FormattedScore' => $leaderboardEntryTwo->score,
                        'DateSubmitted' => $leaderboardEntryTwo->updated_at->toIso8601String(),
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->ID, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => $leaderboard->getRank($leaderboardEntryOne->score),
                            'User' => $userOne->User,
                            'FormattedScore' => $leaderboardEntryOne->score,
                            'DateSubmitted' => $leaderboardEntryOne->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => $leaderboard->getRank($leaderboardEntryTwo->score),
                            'User' => $userTwo->User,
                            'FormattedScore' => $leaderboardEntryTwo->score,
                            'DateSubmitted' => $leaderboardEntryTwo->updated_at->toIso8601String(),
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->ID, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => $leaderboard->getRank($leaderboardEntryFour->score),
                            'User' => $userFour->User,
                            'FormattedScore' => $leaderboardEntryFour->score,
                            'DateSubmitted' => $leaderboardEntryFour->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => $leaderboard->getRank($leaderboardEntryFive->score),
                            'User' => $userFive->User,
                            'FormattedScore' => $leaderboardEntryFive->score,
                            'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->ID, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => $leaderboard->getRank($leaderboardEntryFive->score),
                            'User' => $userFive->User,
                            'FormattedScore' => $leaderboardEntryFive->score,
                            'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => $leaderboard->getRank($leaderboardEntryThree->score),
                            'User' => $userThree->User,
                            'FormattedScore' => $leaderboardEntryThree->score,
                            'DateSubmitted' => $leaderboardEntryThree->updated_at->toIso8601String(),
                        ],
                    ],
                ]);
    }
}
