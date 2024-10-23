<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers\Api;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectJsonResponse(): void
    {
        // Arrange
        $activeGameSystem = System::factory()->create(['ID' => 1, 'name' => 'NES/Famicom', 'name_short' => 'NES', 'active' => true]);
        $inactiveGameSystem = System::factory()->create(['ID' => 2, 'name' => 'PlayStation 5', 'name_short' => 'PS5', 'active' => false]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['Title' => 'AAAAAAA', 'achievements_published' => 50, 'ConsoleID' => $activeGameSystem->id]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['Title' => 'BBBBBBB', 'achievements_published' => 50, 'ConsoleID' => $activeGameSystem->id]);

        // Event, hub, inactive system, and subset games should all be excluded from the "All Games" list.
        Game::factory()->create(['Title' => 'CCCCCCC', 'achievements_published' => 50, 'ConsoleID' => System::Events]);
        Game::factory()->create(['Title' => 'DDDDDDD', 'achievements_published' => 50, 'ConsoleID' => System::Hubs]);
        Game::factory()->create(['Title' => 'EEEEEEE', 'achievements_published' => 50, 'ConsoleID' => $inactiveGameSystem->id]);
        Game::factory()->create(['Title' => 'AAAAAAA [Subset - Bonus]', 'achievements_published' => 50, 'ConsoleID' => $activeGameSystem->id]);

        // Act
        $response = $this->get(route('api.game.index'));

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'currentPage',
                'lastPage',
                'perPage',
                'items',
            ])
            ->assertJsonCount(2, 'items')
            ->assertJson([
                'items' => [
                    [
                        'game' => [
                            'title' => $gameOne->title,
                            'system' => [
                                'id' => $gameOne->system->id,
                            ],
                        ],
                    ],
                    [
                        'game' => [
                            'title' => $gameTwo->title,
                            'system' => [
                                'id' => $gameTwo->system->id,
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
