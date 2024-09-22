<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Actions\AddGameToListAction;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserGameListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->id]);

        $addGameToListAction = new AddGameToListAction();
        $addGameToListAction->execute($user, $games->get(0), UserGameListType::Play);

        // Act
        $response = $this->get(route('game-list.play.index'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('game-list/play')
            ->has('filterableSystemOptions', 1)
            ->where('can.develop', false)
            ->has('paginatedGameListEntries.items', 1)
            ->where('paginatedGameListEntries.items.0.game.title', $games->get(0)->title)
            ->where('paginatedGameListEntries.items.0.game.system.id', $games->get(0)->system->id)
        );
    }
}
