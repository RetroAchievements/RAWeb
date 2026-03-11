<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexWorksForUnauthenticatedVisitors(): void
    {
        // Arrange
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['username' => 'Scott']);

        // Act
        $response = $this->get(route('user.comment.index', ['user' => $targetUser]));

        // Assert
        $response->assertOk();
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0, 'created_at' => now()->subWeeks(3)]);
        $this->actingAs($user);

        /** @var User $targetUser */
        $targetUser = User::factory()->create(['username' => 'Scott']);

        // Act
        $response = $this->get(route('user.comment.index', ['user' => $targetUser]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('targetUser.displayName', 'Scott')

            ->has('paginatedComments.items', 0)

            ->where('isSubscribed', false)
            ->where('canComment', true)
        );
    }

    public function testIndexShowsCanDeleteForWallOwnerOnOtherUsersComment(): void
    {
        // Arrange
        /** @var User $wallOwner */
        $wallOwner = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0, 'created_at' => now()->subWeeks(3)]);
        $this->actingAs($wallOwner);

        /** @var User $commenter */
        $commenter = User::factory()->create();

        Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $wallOwner->id,
            'user_id' => $commenter->id,
        ]);

        // Act
        $response = $this->get(route('user.comment.index', ['user' => $wallOwner]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('paginatedComments.items', 1)
            ->where('paginatedComments.items.0.canDelete', true)
        );
    }

    public function testIndexShowsCanDeleteFalseForNonOwnerNonAuthor(): void
    {
        // Arrange
        /** @var User $viewer */
        $viewer = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0, 'created_at' => now()->subWeeks(3)]);
        $this->actingAs($viewer);

        /** @var User $wallOwner */
        $wallOwner = User::factory()->create();

        /** @var User $commenter */
        $commenter = User::factory()->create();

        Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $wallOwner->id,
            'user_id' => $commenter->id,
        ]);

        // Act
        $response = $this->get(route('user.comment.index', ['user' => $wallOwner]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('paginatedComments.items', 1)
            ->where('paginatedComments.items.0.canDelete', false)
        );
    }

    public function testDestroyAllUnauthorized(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('user.comment.destroyAll', $otherUser->id));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => CommentableType::User->value,
            'commentable_id' => $otherUser->id,
        ]);
    }

    public function testDestroyAllAuthorized(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('user.comment.destroyAll', $user->id));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => CommentableType::User->value,
            'commentable_id' => $user->id,
        ]);
    }
}
