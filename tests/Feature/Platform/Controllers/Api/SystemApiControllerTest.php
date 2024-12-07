<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers\Api;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testGamesReturnsCorrectJsonResponse(): void
    {
        // Arrange
        $system = System::factory()->create(['ID' => 4, 'name' => 'Game Boy', 'name_short' => 'GB', 'active' => true]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['Title' => 'AAAAAAA', 'achievements_published' => 50, 'ConsoleID' => $system->id]);

        // Subset games are currently included, pending a rework.
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['Title' => 'AAAAAAA [Subset - Bonus]', 'achievements_published' => 50, 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('api.system.game.index', ['systemId' => $system->id]));

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
