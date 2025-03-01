<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testShowReturnsCorrectPageForUnauthenticatedUsers(): void
    {
        // Arrange
        $system = System::factory()->create(['ID' => System::Events]);
        $game = Game::factory()->create([
            'Title' => 'Event 001',
            'ConsoleID' => $system->id,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'slug' => 'event-001',
            'active_from' => '2020-01-01',
        ]);

        // Act
        $response = $this->get(route('event.show', $event));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('event.id')
            ->where('event.legacyGame.title', 'Event 001')
            ->where('playerGame', null)
            ->where('playerGameProgressionAwards', null)
            ->etc()
        );
    }

    public function testShowIncludesUserSpecificDataForAuthenticatedUsers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['ID' => System::Events]);
        $game = Game::factory()->create([
            'Title' => 'Event 001',
            'ConsoleID' => $system->id,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'slug' => 'event-001',
            'active_from' => '2020-01-01',
        ]);

        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::EVENT_MANAGER);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('event.show', $event));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('playerGameProgressionAwards')
            ->where('can.manageEvents', true)
        );
    }

    public function testPreventsAccessForFutureEventsForNonEventManagers(): void
    {
        // Arrange
        $system = System::factory()->create(['ID' => System::Events]);
        $game = Game::factory()->create([
            'Title' => 'Event 001',
            'ConsoleID' => $system->id,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'slug' => 'event-001',
            'active_from' => Carbon::now()->addMonth(),
        ]);

        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('event.show', $event));

        // Assert
        $response->assertForbidden();
    }

    public function testAllowsAccessForFutureEventsForEventManagers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['ID' => System::Events]);
        $game = Game::factory()->create([
            'Title' => 'Event 001',
            'ConsoleID' => $system->id,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'slug' => 'event-001',
            'active_from' => Carbon::now()->addMonth(),
        ]);

        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::EVENT_MANAGER);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('event.show', $event));

        // Assert
        $response->assertOk();
    }
}
