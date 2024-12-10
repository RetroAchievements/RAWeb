<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testGamesReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        $system = System::factory()->create(['ID' => 4, 'name' => 'Game Boy', 'name_short' => 'GB', 'active' => true]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['Title' => 'AAAAAAA', 'achievements_published' => 50, 'ConsoleID' => $system->id]);

        // Subset games are currently included, pending a rework.
        Game::factory()->create(['Title' => 'AAAAAAA [Subset - Bonus]', 'achievements_published' => 50, 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('system.game.index', ['system' => $system]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('can.develop', false)
            ->has('paginatedGameListEntries.items', 2)
            ->where('defaultDesktopPageSize', 100)
            ->where('paginatedGameListEntries.items.0.game.title', $gameOne->title)
            ->where('paginatedGameListEntries.items.0.game.system.id', $gameOne->system->id)
        );
    }
}
