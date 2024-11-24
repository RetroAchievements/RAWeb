<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameHashesCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexDoesNotAuthorizeGuests(): void
    {
        // Arrange
        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('game.hashes.comment.index', ['game' => $game]));

        // Assert
        $response->assertForbidden();
    }

    public function testIndexDoesNotAuthorizeJuniorDevelopers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('game.hashes.comment.index', ['game' => $game]));

        // Assert
        $response->assertForbidden();
    }

    public function testIndexDoesAuthorizeDevelopers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('game.hashes.comment.index', ['game' => $game]));

        // Assert
        $response->assertOk();
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('game.hashes.comment.index', ['game' => $game]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('game.id', 1)
            ->where('game.title', $game->title)

            ->has('paginatedComments.items', 0)

            ->where('isSubscribed', false)
            ->where('canComment', true)
        );
    }
}
