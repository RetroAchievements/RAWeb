<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Enums\CommentableType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectsToGamePageWhenCommentIsRecent(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $user = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => $game->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("game/{$game->id}", $location);
        $this->assertStringContainsString('tab=community', $location);
        $this->assertStringContainsString("#comment_{$comment->id}", $location);
    }

    public function testRedirectsToCommentsPageWhenCommentIsOld(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $user = User::factory()->create();

        $oldComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => $game->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        // ... create 20 newer comments to push the old one out of the recent list ...
        for ($i = 0; $i < 20; $i++) {
            Comment::factory()->create([
                'commentable_type' => CommentableType::Game,
                'commentable_id' => $game->id,
                'user_id' => $user->id,
                'created_at' => now()->subDays(29 - $i),
            ]);
        }

        // Act
        $response = $this->get(route('comment.show', ['comment' => $oldComment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("/game/{$game->id}/comments", $location);
        $this->assertStringContainsString("#comment_{$oldComment->id}", $location);
    }

    public function testCorrectlyCalculatesPageNumberForOldComment(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $user = User::factory()->create();

        // ... create the old comment that will be on page 2 (there are 50 comments per page) ...
        $oldComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => $game->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(100),
        ]);

        // ... create 50 newer comments (this fills page 1 completely) ...
        for ($i = 0; $i < 50; $i++) {
            Comment::factory()->create([
                'commentable_type' => CommentableType::Game,
                'commentable_id' => $game->id,
                'user_id' => $user->id,
                'created_at' => now()->subDays(50 - $i),
            ]);
        }

        // Act
        $response = $this->get(route('comment.show', ['comment' => $oldComment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // The old comment is the first in chronological order, so it should be on page 1.
        // But since there are 51 total comments and the old one is first (position 0),
        // page = ceil((0 + 1) / 50) = 1
        $this->assertStringContainsString('page=1', $location);
    }

    public function testReturns404ForSoftDeletedComment(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $user = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => $game->id,
            'user_id' => $user->id,
        ]);

        $comment->delete();

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment->id]));

        // Assert
        $response->assertNotFound();
    }

    public function testReturns404ForUnsupportedCommentableType(): void
    {
        // Arrange
        $user = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Forum,
            'commentable_id' => 1,
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertNotFound();
    }

    public function testReturns404ForDisabledUserWall(): void
    {
        // Arrange
        $wallOwner = User::factory()->create(['is_user_wall_active' => false]);
        $commenter = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $wallOwner->id,
            'user_id' => $commenter->id,
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertNotFound();
    }

    public function testRedirectsToUserPageForRecentUserWallComment(): void
    {
        // Arrange
        $wallOwner = User::factory()->create(['is_user_wall_active' => true]);
        $commenter = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $wallOwner->id,
            'user_id' => $commenter->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("user/{$wallOwner->username}", $location);
        $this->assertStringContainsString("#comment_{$comment->id}", $location);
    }

    public function testRedirectsToAchievementPageForRecentComment(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("achievement/{$achievement->id}", $location);
        $this->assertStringContainsString("#comment_{$comment->id}", $location);
    }

    public function testRedirectsToLeaderboardPageForRecentComment(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Leaderboard,
            'commentable_id' => $leaderboard->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("leaderboard/{$leaderboard->id}", $location);
        $this->assertStringContainsString("#comment_{$comment->id}", $location);
    }

    public function testReturns404ForNonExistentResource(): void
    {
        // Arrange
        $user = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => 99999999, // !! doesn't exist
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->get(route('comment.show', ['comment' => $comment]));

        // Assert
        $response->assertNotFound();
    }

    public function testRedirectsToAchievementCommentsPageWhenCommentIsOld(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create();

        $oldComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        // ... create 20 newer comments to push the old one out of the recent list ...
        for ($i = 0; $i < 20; $i++) {
            Comment::factory()->create([
                'commentable_type' => CommentableType::Achievement,
                'commentable_id' => $achievement->id,
                'user_id' => $user->id,
                'created_at' => now()->subDays(29 - $i),
            ]);
        }

        // Act
        $response = $this->get(route('comment.show', ['comment' => $oldComment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("/achievement/{$achievement->id}/comments", $location);
        $this->assertStringContainsString("#comment_{$oldComment->id}", $location);
    }

    public function testRedirectsToUserCommentsPageWhenCommentIsOld(): void
    {
        // Arrange
        $wallOwner = User::factory()->create(['is_user_wall_active' => true]);
        $commenter = User::factory()->create();

        $oldComment = Comment::factory()->create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $wallOwner->id,
            'user_id' => $commenter->id,
            'created_at' => now()->subDays(30),
        ]);

        // ... create 20 newer comments to push the old one out of the recent list ...
        for ($i = 0; $i < 20; $i++) {
            Comment::factory()->create([
                'commentable_type' => CommentableType::User,
                'commentable_id' => $wallOwner->id,
                'user_id' => $commenter->id,
                'created_at' => now()->subDays(29 - $i),
            ]);
        }

        // Act
        $response = $this->get(route('comment.show', ['comment' => $oldComment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("/user/{$wallOwner->username}/comments", $location);
        $this->assertStringContainsString("#comment_{$oldComment->id}", $location);
    }

    public function testRedirectsToLeaderboardCommentsPageWhenCommentIsOld(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create();

        $oldComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Leaderboard,
            'commentable_id' => $leaderboard->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        // ... create 20 newer comments to push the old one out of the recent list ...
        for ($i = 0; $i < 20; $i++) {
            Comment::factory()->create([
                'commentable_type' => CommentableType::Leaderboard,
                'commentable_id' => $leaderboard->id,
                'user_id' => $user->id,
                'created_at' => now()->subDays(29 - $i),
            ]);
        }

        // Act
        $response = $this->get(route('comment.show', ['comment' => $oldComment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("/leaderboard/{$leaderboard->id}/comments", $location);
        $this->assertStringContainsString("#comment_{$oldComment->id}", $location);
    }
}
