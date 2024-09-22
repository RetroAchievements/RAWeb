<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers\Api;

use App\Community\Actions\AddGameToListAction;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGameListApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectJsonResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->id]);

        $addGameToListAction = new AddGameToListAction();
        $addGameToListAction->execute($user, $games->get(0), UserGameListType::Play);

        // Act
        $response = $this->get(route('api.user-game-list.index'));

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
            ->assertJsonCount(1, 'items')
            ->assertJson([
                'items' => [
                    [
                        'game' => [
                            'title' => $games->get(0)->title,
                            'system' => [
                                'id' => $games->get(0)->system->id,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function testStoreAddsGameToUserBacklog(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        // Act
        $response = $this->postJson(route('api.user-game-list.store', ['game' => $game->id]), [
            'userGameListType' => UserGameListType::Play,
        ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'GameID' => $game->id,
                    'type' => UserGameListType::Play,
                ],
            ]);

        $this->assertDatabaseHas(UserGameListEntry::getFullTableName(), [
            'user_id' => $user->id,
            'GameID' => $game->id,
            'type' => UserGameListType::Play,
        ]);
    }

    public function testDestroyRemovesGameFromUserBacklog(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $addGameToListAction = new AddGameToListAction();
        $addGameToListAction->execute($user, $game, UserGameListType::Play);

        // Act
        $response = $this->deleteJson(route('api.user-game-list.destroy', ['game' => $game->id]), [
            'userGameListType' => UserGameListType::Play,
        ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing(UserGameListEntry::getFullTableName(), [
            'user_id' => $user->id,
            'GameID' => $game->id,
            'type' => UserGameListType::Play,
        ]);
    }
}
