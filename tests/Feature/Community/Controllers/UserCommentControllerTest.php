<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Enums\ArticleType;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserCommentControllerTest extends TestCase
{
    use RefreshDatabase;

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
            'ArticleType' => ArticleType::User,
            'ArticleID' => $otherUser->id,
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('user.comment.destroyAll', $otherUser->id));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('Comment', [
            'ArticleType' => ArticleType::User,
            'ArticleID' => $otherUser->id,
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
            'ArticleType' => ArticleType::User,
            'ArticleID' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('user.comment.destroyAll', $user->id));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('Comment', [
            'ArticleType' => ArticleType::User,
            'ArticleID' => $user->id,
        ]);
    }
}
