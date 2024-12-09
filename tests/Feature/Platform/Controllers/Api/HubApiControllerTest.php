<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers\Api;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Platform\Enums\GameSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testGamesReturnsCorrectJsonResponse(): void
    {
        // Arrange
        $hub = GameSet::factory()->create([
            'title' => '[Genre - RPG]',
            'type' => GameSetType::Hub,
        ]);

        $system = System::factory()->create(['ID' => 4, 'name' => 'Game Boy', 'name_short' => 'GB', 'active' => true]);

        $gameOne = Game::factory()->create(['Title' => 'Test Game 1', 'ConsoleID' => $system->id, 'achievements_published' => 50]);
        $gameTwo = Game::factory()->create(['Title' => 'Test Game 2', 'ConsoleID' => $system->id, 'achievements_published' => 0]);

        $hub->games()->attach([$gameOne->id, $gameTwo->id]);

        // Act
        $response = $this->get(route('api.hub.game.index', ['gameSet' => $hub]));

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
                        ],
                    ],
                    [
                        'game' => [
                            'title' => $gameTwo->title,
                        ],
                    ],
                ],
            ]);
    }

    public function testRandomGameReturnsCorrectJsonResponse(): void
    {
        // Arrange
        $hub = GameSet::factory()->create([
            'title' => '[Genre - RPG]',
            'type' => GameSetType::Hub,
        ]);

        $system = System::factory()->create(['ID' => 4, 'name' => 'Game Boy', 'name_short' => 'GB', 'active' => true]);
        $game = Game::factory()->create(['Title' => 'Test Game', 'ConsoleID' => $system->id, 'achievements_published' => 50]);

        $hub->games()->attach($game->id);

        // Act
        $response = $this->get(route('api.hub.game.random', ['gameSet' => $hub]));

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure(['gameId'])
            ->assertJson([
                'gameId' => $game->id,
            ]);
    }
}
