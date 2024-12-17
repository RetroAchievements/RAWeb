<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserModerationCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexDoesNotAuthorizeGuests(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->get(route('user.moderation-comment.index', ['user' => $user]));

        // Assert
        $response->assertForbidden();
    }

    public function testIndexDoesNotAuthorizeDevelopers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        $user = User::factory()->create();

        // Act
        $response = $this->get(route('user.moderation-comment.index', ['user' => $user]));

        // Assert
        $response->assertForbidden();
    }

    public function testIndexDoesAuthorizeModerators(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::MODERATOR);
        $this->actingAs($user);

        $user = User::factory()->create();

        // Act
        $response = $this->get(route('user.moderation-comment.index', ['user' => $user]));

        // Assert
        $response->assertOk();
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::MODERATOR);
        $this->actingAs($user);

        $user = User::factory()->create(['User' => 'Scott']);

        // Act
        $response = $this->get(route('user.moderation-comment.index', ['user' => $user]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('targetUser.displayName', 'Scott')

            ->has('paginatedComments.items', 0)

            ->where('canComment', true)
        );
    }
}
