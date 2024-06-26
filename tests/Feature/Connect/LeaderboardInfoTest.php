<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
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
        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);

        $this->get($this->apiUrl('lbinfo', ['i' => $leaderboard->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardData' => [
                    'ConsoleID' => $system->id,
                    'ConsoleName' => $system->name,
                    'Entries' => [],
                    'GameID' => $game->id,
                    'GameIcon' => $game->ImageIcon,
                    'GameTitle' => $game->title,
                    'ForumTopicID' => $game->ForumTopicID,
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
    }
}
