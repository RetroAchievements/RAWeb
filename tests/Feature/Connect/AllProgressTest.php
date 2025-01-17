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
                        'Achievements' => $game->achievements_published,
                        'Unlocked' => 10,
                        'UnlockedHardcore' => 6,
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

    public function testItIgnoresGamesWithNoProgress(): void
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'achievements_published' => 12,
        ]);

        PlayerGame::create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 0,
            'achievements_unlocked' => 0,
        ]);

        $this->get($this->apiUrl('allprogress', ['c' => $system->id]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [],
            ]);
    }

    public function testItIgnoresGamesFromOtherConsoles(): void
    {
        $system = System::factory()->create();
        $otherSystem = System::factory()->create();

        $game = Game::factory()->create([
            'ConsoleID' => $otherSystem->id,  // !! different console
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
                'Response' => [],
            ]);
    }
}
