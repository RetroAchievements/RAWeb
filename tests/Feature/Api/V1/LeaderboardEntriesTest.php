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
            'game_id' => $game->ID,
            'title' => "Test leaderboard 1",
            'description' => "I am the first leaderboard",
        ]);

        $userOne = User::factory()->create(['User' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 1,
        ]);

        $userTwo = User::factory()->create(['User' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userTwo->ID,
            'score' => 1,
        ]);

        $userThree = User::factory()->create(['User' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userThree->ID,
            'score' => 100,
        ]);

        $userFour = User::factory()->create(['User' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userFour->ID,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['User' => 'myUser5']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $userFive->ID,
            'score' => 200,
        ]);

        /** @var Leaderboard $timedLeaderboard */
        $timedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->ID,
            'title' => "Test leaderboard 2",
            'description' => "I am a timed leaderboard",
            'format' => "TIME",
        ]);

        $timedLeaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $timedLeaderboard->id,
            'user_id' => $userOne->ID,
            'score' => 123,
        ]);

        $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->id]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Rank" => 1,
                        'User' => $userFour->User,
                        'ULID' => $userFour->ulid,
                        'Score' => $leaderboardEntryFour->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryFour->score, $leaderboard->format),
                        'DateSubmitted' => $leaderboardEntryFour->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 2,
                        'User' => $userFive->User,
                        'ULID' => $userFive->ulid,
                        'Score' => $leaderboardEntryFive->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryFive->score, $leaderboard->format),
                        'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 3,
                        'User' => $userThree->User,
                        'ULID' => $userThree->ulid,
                        'Score' => $leaderboardEntryThree->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->format),
                        'DateSubmitted' => $leaderboardEntryThree->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 4,
                        'User' => $userOne->User,
                        'ULID' => $userOne->ulid,
                        'Score' => $leaderboardEntryOne->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->format),
                        'DateSubmitted' => $leaderboardEntryOne->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 4,
                        'User' => $userTwo->User,
                        'ULID' => $userTwo->ulid,
                        'Score' => $leaderboardEntryTwo->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
                        'DateSubmitted' => $leaderboardEntryTwo->updated_at->toIso8601String(),
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->id, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => 4,
                            'User' => $userOne->User,
                            'ULID' => $userOne->ulid,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->format),
                            'DateSubmitted' => $leaderboardEntryOne->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => 4,
                            'User' => $userTwo->User,
                            'ULID' => $userTwo->ulid,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->format),
                            'DateSubmitted' => $leaderboardEntryTwo->updated_at->toIso8601String(),
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->id, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => 1,
                            'User' => $userFour->User,
                            'ULID' => $userFour->ulid,
                            'Score' => $leaderboardEntryFour->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFour->score, $leaderboard->format),
                            'DateSubmitted' => $leaderboardEntryFour->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => 2,
                            'User' => $userFive->User,
                            'ULID' => $userFive->ulid,
                            'Score' => $leaderboardEntryFive->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFive->score, $leaderboard->format),
                            'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->id, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "Rank" => 2,
                            'User' => $userFive->User,
                            'ULID' => $userFive->ulid,
                            'Score' => $leaderboardEntryFive->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFive->score, $leaderboard->format),
                            'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => 3,
                            'User' => $userThree->User,
                            'ULID' => $userThree->ulid,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->format),
                            'DateSubmitted' => $leaderboardEntryThree->updated_at->toIso8601String(),
                        ],
                    ],
                ]);

                $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $timedLeaderboard->id]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 1,
                    'Total' => 1,
                    'Results' => [
                        [
                            "Rank" => 1,
                            'User' => $userOne->User,
                            'ULID' => $userOne->ulid,
                            'Score' => $timedLeaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($timedLeaderboardEntryOne->score, $timedLeaderboard->format),
                            'DateSubmitted' => $timedLeaderboardEntryOne->updated_at->toIso8601String(),
                        ],
                    ],
                ]);
    }
}
