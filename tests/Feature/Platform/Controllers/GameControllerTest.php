<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        $activeGameSystem = System::factory()->create(['ID' => 1, 'name' => 'NES/Famicom', 'name_short' => 'NES', 'active' => true]);
        $inactiveGameSystem = System::factory()->create(['ID' => 2, 'name' => 'PlayStation 5', 'name_short' => 'PS5', 'active' => false]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['Title' => 'AAAAAAA', 'achievements_published' => 50, 'ConsoleID' => $activeGameSystem->id]);
        Game::factory()->create(['Title' => 'BBBBBBB', 'achievements_published' => 50, 'ConsoleID' => $activeGameSystem->id]);

        // Event, hub, inactive system, and subset games should all be excluded from the "All Games" list.
        Game::factory()->create(['Title' => 'CCCCCCC', 'achievements_published' => 50, 'ConsoleID' => System::Events]);
        Game::factory()->create(['Title' => 'DDDDDDD', 'achievements_published' => 50, 'ConsoleID' => System::Hubs]);
        Game::factory()->create(['Title' => 'EEEEEEE', 'achievements_published' => 50, 'ConsoleID' => $inactiveGameSystem->id]);
        Game::factory()->create(['Title' => 'AAAAAAA [Subset - Bonus]', 'achievements_published' => 50, 'ConsoleID' => $activeGameSystem->id]);

        // Act
        $response = $this->get(route('game.index'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('can.develop', false)
            ->has('paginatedGameListEntries.items', 2)
            ->where('defaultDesktopPageSize', 25)
            ->where('paginatedGameListEntries.items.0.game.title', $gameOne->title)
            ->where('paginatedGameListEntries.items.0.game.system.id', $gameOne->system->id)
        );
    }

    public function testDevInterestDeniesAccessToRegularUsers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('game.dev-interest', ['game' => $game]));

        // Assert
        $response->assertForbidden();
    }

    public function testDevInterestDeniesAccessToJuniorDevelopers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('game.dev-interest', ['game' => $game]));

        // Assert
        $response->assertForbidden();
    }

    public function testDevInterestDeniesAccessToFullDevelopers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('game.dev-interest', ['game' => $game]));

        // Assert
        $response->assertForbidden();
    }

    public function testDevInterestIsAuthorizedForFullDevelopersWithPrimaryActiveClaims(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $response = $this->get(route('game.dev-interest', ['game' => $game]));

        // Assert
        $response->assertOk();
    }

    public function testDevInterestDeniesAccessForDevelopersWithCollaborationClaims(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Collaboration,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $response = $this->get(route('game.dev-interest', ['game' => $game]));

        // Assert
        $response->assertForbidden();
    }

    public function testDevInterestReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $response = $this->get(route('game.dev-interest', ['game' => $game]));

        // Assert
        $response->assertInertia(fn ($page) => $page
            ->has('game')
            ->where('game.id', $game->id)
            ->has('developers')
            ->etc()
        );
    }
}
