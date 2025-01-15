<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllProgressTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testItReturnsUserProgress(): void
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'achievements_published' => 12,
        ]);

        PlayerGame::create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 6,
            'achievements_unlocked' => 10,
        ]);

        $this->get($this->apiUrl('allprogress', ['c' => $system->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    $game->id => [
                        'NumAch' => $game->achievements_published,
                        'Earned' => 10,
                        'HCEarned' => 6,
                    ],
                ],
            ]);
    }

    public function testItIgnoresGamesWithNoPublishedAchievements(): void
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'achievements_published' => 0, // !! set was demoted
        ]);

        PlayerGame::create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 6,
            'achievements_unlocked' => 10,
        ]);

        $this->get($this->apiUrl('allprogress', ['c' => $system->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [],
            ]);
    }
}
