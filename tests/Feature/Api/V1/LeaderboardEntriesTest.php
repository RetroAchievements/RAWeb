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
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 1",
            'Description' => "I am the first leaderboard",
        ]);

        $userOne = User::factory()->create(['username' => 'myUser1']);
        $leaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 1,
        ]);

        $userTwo = User::factory()->create(['username' => 'myUser2']);
        $leaderboardEntryTwo = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userTwo->id,
            'score' => 1,
        ]);

        $userThree = User::factory()->create(['username' => 'myUser3']);
        $leaderboardEntryThree = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userThree->id,
            'score' => 100,
        ]);

        $userFour = User::factory()->create(['username' => 'myUser4']);
        $leaderboardEntryFour = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFour->id,
            'score' => 300,
        ]);

        $userFive = User::factory()->create(['username' => 'myUser5']);
        $leaderboardEntryFive = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->ID,
            'user_id' => $userFive->id,
            'score' => 200,
        ]);

        /** @var Leaderboard $timedLeaderboard */
        $timedLeaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'Title' => "Test leaderboard 2",
            'Description' => "I am a timed leaderboard",
            'Format' => "TIME",
        ]);

        $timedLeaderboardEntryOne = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $timedLeaderboard->ID,
            'user_id' => $userOne->id,
            'score' => 123,
        ]);

        $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $leaderboard->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "Rank" => 1,
                        'User' => $userFour->username,
                        'ULID' => $userFour->ulid,
                        'Score' => $leaderboardEntryFour->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryFour->score, $leaderboard->Format),
                        'DateSubmitted' => $leaderboardEntryFour->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 2,
                        'User' => $userFive->username,
                        'ULID' => $userFive->ulid,
                        'Score' => $leaderboardEntryFive->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryFive->score, $leaderboard->Format),
                        'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 3,
                        'User' => $userThree->username,
                        'ULID' => $userThree->ulid,
                        'Score' => $leaderboardEntryThree->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->Format),
                        'DateSubmitted' => $leaderboardEntryThree->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 4,
                        'User' => $userOne->username,
                        'ULID' => $userOne->ulid,
                        'Score' => $leaderboardEntryOne->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->Format),
                        'DateSubmitted' => $leaderboardEntryOne->updated_at->toIso8601String(),
                    ],
                    [
                        "Rank" => 4,
                        'User' => $userTwo->username,
                        'ULID' => $userTwo->ulid,
                        'Score' => $leaderboardEntryTwo->score,
                        'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
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
                            "Rank" => 4,
                            'User' => $userOne->username,
                            'ULID' => $userOne->ulid,
                            'Score' => $leaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryOne->score, $leaderboard->Format),
                            'DateSubmitted' => $leaderboardEntryOne->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => 4,
                            'User' => $userTwo->username,
                            'ULID' => $userTwo->ulid,
                            'Score' => $leaderboardEntryTwo->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryTwo->score, $leaderboard->Format),
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
                            "Rank" => 1,
                            'User' => $userFour->username,
                            'ULID' => $userFour->ulid,
                            'Score' => $leaderboardEntryFour->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFour->score, $leaderboard->Format),
                            'DateSubmitted' => $leaderboardEntryFour->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => 2,
                            'User' => $userFive->username,
                            'ULID' => $userFive->ulid,
                            'Score' => $leaderboardEntryFive->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFive->score, $leaderboard->Format),
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
                            "Rank" => 2,
                            'User' => $userFive->username,
                            'ULID' => $userFive->ulid,
                            'Score' => $leaderboardEntryFive->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryFive->score, $leaderboard->Format),
                            'DateSubmitted' => $leaderboardEntryFive->updated_at->toIso8601String(),
                        ],
                        [
                            "Rank" => 3,
                            'User' => $userThree->username,
                            'ULID' => $userThree->ulid,
                            'Score' => $leaderboardEntryThree->score,
                            'FormattedScore' => ValueFormat::format($leaderboardEntryThree->score, $leaderboard->Format),
                            'DateSubmitted' => $leaderboardEntryThree->updated_at->toIso8601String(),
                        ],
                    ],
                ]);

                $this->get($this->apiUrl('GetLeaderboardEntries', ['i' => $timedLeaderboard->ID]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 1,
                    'Total' => 1,
                    'Results' => [
                        [
                            "Rank" => 1,
                            'User' => $userOne->username,
                            'ULID' => $userOne->ulid,
                            'Score' => $timedLeaderboardEntryOne->score,
                            'FormattedScore' => ValueFormat::format($timedLeaderboardEntryOne->score, $timedLeaderboard->Format),
                            'DateSubmitted' => $timedLeaderboardEntryOne->updated_at->toIso8601String(),
                        ],
                    ],
                ]);
    }
}
