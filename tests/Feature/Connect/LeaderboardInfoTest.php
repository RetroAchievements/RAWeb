<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaderboardInfoTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testLeaderboardInfo(): void
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $game = $this->seedGame();
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'LowerIsBetter' => false,
            'Format' => ValueFormat::Score,
        ]);

        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'Entries' => [],
                    'GameID' => $game->id,
                    'LBAuthor' => $leaderboard->developer?->User,
                    'LBCreated' => $now->format('Y-m-d H:i:s'),
                    'LBDesc' => $leaderboard->description,
                    'LBFormat' => $leaderboard->format,
                    'LBID' => $leaderboard->id,
                    'LBMem' => $leaderboard->Mem,
                    'LBTitle' => $leaderboard->title,
                    'LBUpdated' => $now->format('Y-m-d H:i:s'),
                    'LowerIsBetter' => (int) $leaderboard->rank_asc,
                    'TotalEntries' => 0,
                ],
            ]);

        /** @var User $playerOne */
        $playerOne = User::factory()->create();
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create();
        /** @var User $playerThree */
        $playerThree = User::factory()->create();

        SubmitLeaderboardEntry($playerTwo, $leaderboard, 500, null);
        SubmitLeaderboardEntry($playerOne, $leaderboard, 300, null);
        SubmitLeaderboardEntry($playerThree, $leaderboard, 100, null);

        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'Entries' => [
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 1,
                            'Rank' => 1,
                            'Score' => 500,
                            'User' => $playerTwo->display_name,
                            'ULID' => $playerTwo->ulid,
                            'AvatarUrl' => $playerTwo->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 2,
                            'Rank' => 2,
                            'Score' => 300,
                            'User' => $playerOne->display_name,
                            'ULID' => $playerOne->ulid,
                            'AvatarUrl' => $playerOne->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 3,
                            'Rank' => 3,
                            'Score' => 100,
                            'User' => $playerThree->display_name,
                            'ULID' => $playerThree->ulid,
                            'AvatarUrl' => $playerThree->avatar_url,
                        ],
                    ],
                    'GameID' => $game->id,
                    'LBAuthor' => $leaderboard->developer?->User,
                    'LBCreated' => $now->format('Y-m-d H:i:s'),
                    'LBDesc' => $leaderboard->description,
                    'LBFormat' => $leaderboard->format,
                    'LBID' => $leaderboard->id,
                    'LBMem' => $leaderboard->Mem,
                    'LBTitle' => $leaderboard->title,
                    'LBUpdated' => $now->format('Y-m-d H:i:s'),
                    'LowerIsBetter' => (int) $leaderboard->rank_asc,
                    'TotalEntries' => 3,
                ],
            ]);
    }

    public function testLeaderboardInfoNearUser(): void
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $game = $this->seedGame();
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'LowerIsBetter' => 1,
            'Format' => ValueFormat::Score,
        ]);

        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'Entries' => [],
                    'GameID' => $game->id,
                    'LBAuthor' => $leaderboard->developer?->User,
                    'LBCreated' => $now->format('Y-m-d H:i:s'),
                    'LBDesc' => $leaderboard->description,
                    'LBFormat' => $leaderboard->format,
                    'LBID' => $leaderboard->id,
                    'LBMem' => $leaderboard->Mem,
                    'LBTitle' => $leaderboard->title,
                    'LBUpdated' => $now->format('Y-m-d H:i:s'),
                    'LowerIsBetter' => (int) $leaderboard->rank_asc,
                    'TotalEntries' => 0,
                ],
            ]);

        /** @var User $playerOne */
        $playerOne = User::factory()->create();
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create();
        /** @var User $playerThree */
        $playerThree = User::factory()->create();
        /** @var User $playerFour */
        $playerFour = User::factory()->create();
        /** @var User $playerFive */
        $playerFive = User::factory()->create();
        /** @var User $playerSix */
        $playerSix = User::factory()->create();
        /** @var User $playerSeven */
        $playerSeven = User::factory()->create();
        /** @var User $playerEight */
        $playerEight = User::factory()->create();

        // 7 = 100
        // 4 = 100 (later)
        // 2 = 200
        // 1 = 300
        // 3 = 300 (later)
        // 5 = 400
        // 6 = 500
        // 8 = 500 (later)

        SubmitLeaderboardEntry($playerOne, $leaderboard, 300, null);
        SubmitLeaderboardEntry($playerTwo, $leaderboard, 200, null);
        SubmitLeaderboardEntry($playerFive, $leaderboard, 400, null);
        SubmitLeaderboardEntry($playerSix, $leaderboard, 500, null);
        SubmitLeaderboardEntry($playerSeven, $leaderboard, 100, null);

        $later = $now->clone()->addMinutes(3);
        Carbon::setTestNow($later);
        SubmitLeaderboardEntry($playerThree, $leaderboard, 300, null);
        SubmitLeaderboardEntry($playerFour, $leaderboard, 100, null);
        SubmitLeaderboardEntry($playerEight, $leaderboard, 500, null);

        // near user in middle returns items around user
        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id, 'u' => $playerFive->display_name, 'c' => 3]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'Entries' => [
                        [
                            'DateSubmitted' => $later->getTimestamp(),
                            'Index' => 5,
                            'Rank' => 4,
                            'Score' => 300,
                            'User' => $playerThree->display_name,
                            'ULID' => $playerThree->ulid,
                            'AvatarUrl' => $playerThree->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 6,
                            'Rank' => 6,
                            'Score' => 400,
                            'User' => $playerFive->display_name,
                            'ULID' => $playerFive->ulid,
                            'AvatarUrl' => $playerFive->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 7,
                            'Rank' => 7,
                            'Score' => 500,
                            'User' => $playerSix->display_name,
                            'ULID' => $playerSix->ulid,
                            'AvatarUrl' => $playerSix->avatar_url,
                        ],
                    ],
                    'GameID' => $game->id,
                    'LBAuthor' => $leaderboard->developer?->User,
                    'LBCreated' => $now->format('Y-m-d H:i:s'),
                    'LBDesc' => $leaderboard->description,
                    'LBFormat' => $leaderboard->format,
                    'LBID' => $leaderboard->id,
                    'LBMem' => $leaderboard->Mem,
                    'LBTitle' => $leaderboard->title,
                    'LBUpdated' => $now->format('Y-m-d H:i:s'),
                    'LowerIsBetter' => (int) $leaderboard->rank_asc,
                    'TotalEntries' => 8,
                ],
            ]);

        // near first user returns first N entries
        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id, 'u' => $playerSeven->display_name, 'c' => 3]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'Entries' => [
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 1,
                            'Rank' => 1,
                            'Score' => 100,
                            'User' => $playerSeven->display_name,
                            'ULID' => $playerSeven->ulid,
                            'AvatarUrl' => $playerSeven->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $later->getTimestamp(),
                            'Index' => 2,
                            'Rank' => 1,
                            'Score' => 100,
                            'User' => $playerFour->display_name,
                            'ULID' => $playerFour->ulid,
                            'AvatarUrl' => $playerFour->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 3,
                            'Rank' => 3,
                            'Score' => 200,
                            'User' => $playerTwo->display_name,
                            'ULID' => $playerTwo->ulid,
                            'AvatarUrl' => $playerTwo->avatar_url,
                        ],
                    ],
                    'GameID' => $game->id,
                    'LBAuthor' => $leaderboard->developer?->User,
                    'LBCreated' => $now->format('Y-m-d H:i:s'),
                    'LBDesc' => $leaderboard->description,
                    'LBFormat' => $leaderboard->format,
                    'LBID' => $leaderboard->id,
                    'LBMem' => $leaderboard->Mem,
                    'LBTitle' => $leaderboard->title,
                    'LBUpdated' => $now->format('Y-m-d H:i:s'),
                    'LowerIsBetter' => (int) $leaderboard->rank_asc,
                    'TotalEntries' => 8,
                ],
            ]);
        // near last user returns last N entries
        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id, 'u' => $playerEight->display_name, 'c' => 3]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'Entries' => [
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 6,
                            'Rank' => 6,
                            'Score' => 400,
                            'User' => $playerFive->display_name,
                            'ULID' => $playerFive->ulid,
                            'AvatarUrl' => $playerFive->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 7,
                            'Rank' => 7,
                            'Score' => 500,
                            'User' => $playerSix->display_name,
                            'ULID' => $playerSix->ulid,
                            'AvatarUrl' => $playerSix->avatar_url,
                        ],
                        [
                            'DateSubmitted' => $later->getTimestamp(),
                            'Index' => 8,
                            'Rank' => 7,
                            'Score' => 500,
                            'User' => $playerEight->display_name,
                            'ULID' => $playerEight->ulid,
                            'AvatarUrl' => $playerEight->avatar_url,
                        ],
                    ],
                    'GameID' => $game->id,
                    'LBAuthor' => $leaderboard->developer?->User,
                    'LBCreated' => $now->format('Y-m-d H:i:s'),
                    'LBDesc' => $leaderboard->description,
                    'LBFormat' => $leaderboard->format,
                    'LBID' => $leaderboard->id,
                    'LBMem' => $leaderboard->Mem,
                    'LBTitle' => $leaderboard->title,
                    'LBUpdated' => $now->format('Y-m-d H:i:s'),
                    'LowerIsBetter' => (int) $leaderboard->rank_asc,
                    'TotalEntries' => 8,
                ],
            ]);
    }
}
