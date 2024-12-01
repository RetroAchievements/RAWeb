<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers\Api;

use App\Enums\Permissions;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePlayersApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectJsonResponse(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        // Act
        $response = $this->getJson(route('api.active-player.index'));

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'currentPage',
                'lastPage',
                'perPage',
                'items',
            ]);
    }

    public function testIndexSupportsPagination(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->count(5)->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        // Act
        $response = $this->getJson(route('api.active-player.index', [
            'perPage' => 2,
            'page' => 2,
        ]));

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'currentPage' => 2,
                'perPage' => 2,
            ])
            ->assertJsonCount(2, 'items');
    }
}
