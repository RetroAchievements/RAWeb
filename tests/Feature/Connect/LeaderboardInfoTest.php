<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
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

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
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
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 2,
                            'Rank' => 2,
                            'Score' => 300,
                            'User' => $playerOne->display_name,
                        ],
                        [
                            'DateSubmitted' => $now->getTimestamp(),
                            'Index' => 3,
                            'Rank' => 3,
                            'Score' => 100,
                            'User' => $playerThree->display_name,
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
}
