<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\CommentableType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetComments', ['i' => '1', 't' => 3]))
            ->assertJsonMissingValidationErrors([
                'i',
            ]);

        $this->get($this->apiUrl('GetComments', ['i' => 1, 't' => 1]))
            ->assertJsonMissingValidationErrors([
                'i',
                't',
            ]);

        $this->get($this->apiUrl('GetComments', ['i' => 'invalid', 't' => 2]))
            ->assertJsonValidationErrors(['i']);

        $this->get($this->apiUrl('GetComments', ['i' => 'not-an-integer', 't' => 1]))
            ->assertJsonValidationErrors(['i']);

        $this->get($this->apiUrl('GetComments', ['i' => 1, 't' => 1, 'sort' => 'not-an-integer']))
            ->assertJsonValidationErrors(['sort']);

        $this->get($this->apiUrl('GetComments', ['i' => 1, 't' => 1, 'sort' => 3]))
            ->assertJsonValidationErrors(['sort']);

        $this->get($this->apiUrl('GetComments', ['i' => null, 't' => 2]))
            ->assertJsonValidationErrors(['i']);
    }

    public function testGetCommentsUnknownUser(): void
    {
        $this->get($this->apiUrl('GetComments', ['i' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetCommentsForAchievement(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $bannedUser = User::factory()->create(['ID' => 309, 'banned_at' => Carbon::now()->subDay()]);

        $achievement = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $user1->ID]);
        $comment1 = Comment::factory()->create([
            'commentable_id' => $achievement->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user1->ID,
            'body' => 'This is a great achievement!',
            'created_at' => "2024-01-18T15:01:04.000000Z",
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $achievement->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user2->ID,
            'body' => 'I agree, this is awesome!',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);
        $comment3 = Comment::factory()->create([
            'commentable_id' => $achievement->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $bannedUser->ID,
            'body' => 'This comment is from a banned user!',
            'created_at' => "2024-01-20T15:01:04.000000Z",
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $achievement->ID, 't' => 2]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'Count' => 2,
            'Total' => 2,
            'Results' => [
                [
                    'User' => $user1->User,
                    'ULID' => $user1->ulid,
                    'Submitted' => $comment1->created_at->toISOString(),
                    'CommentText' => $comment1->body,
                ],
                [
                    'User' => $user2->User,
                    'ULID' => $user2->ulid,
                    'Submitted' => $comment2->created_at->toISOString(),
                    'CommentText' => $comment2->body,
                ],
            ],
        ]);
    }

    public function testGetCommentsForAchievementDescendingOrder(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $bannedUser = User::factory()->create(['ID' => 309, 'banned_at' => Carbon::now()->subDay()]);

        $achievement = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $user1->ID]);
        $comment1 = Comment::factory()->create([
            'commentable_id' => $achievement->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user1->ID,
            'body' => 'This is a great achievement!',
            'created_at' => "2024-01-18T15:01:04.000000Z",
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $achievement->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user2->ID,
            'body' => 'I agree, this is awesome!',
            'created_at' => "2024-12-18T15:01:04.000000Z",
        ]);
        $comment3 = Comment::factory()->create([
            'commentable_id' => $achievement->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $bannedUser->ID,
            'body' => 'This comment is from a banned user!',
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $achievement->ID, 't' => 2, 'sort' => '-submitted']))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'Count' => 2,
            'Total' => 2,
            'Results' => [
                [
                    'User' => $user2->User,
                    'Submitted' => $comment2->created_at->toISOString(),
                    'CommentText' => $comment2->body,
                ],
                [
                    'User' => $user1->User,
                    'Submitted' => $comment1->created_at->toISOString(),
                    'CommentText' => $comment1->body,
                ],
            ],
        ]);
    }

    public function testGetCommentsForGame(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);

        $comment1 = Comment::factory()->create([
            'commentable_id' => $game->ID,
            'commentable_type' => CommentableType::Game,
            'user_id' => $user1->ID,
            'body' => 'This is a great achievement!',
            'created_at' => "2024-01-18T15:01:04.000000Z",
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $game->ID,
            'commentable_type' => CommentableType::Game,
            'user_id' => $user2->ID,
            'body' => 'I agree, this is awesome!',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);
        $comment3 = Comment::factory()->create([
            'commentable_id' => $game->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $bannedUser->ID,
            'body' => 'This comment is from a banned user!',
            'created_at' => "2024-01-20T15:01:04.000000Z",
        ]);
        $deletedComment = Comment::factory()->create([
            'commentable_id' => $game->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user1->ID,
            'body' => 'This comment has been deleted!',
            'created_at' => "2024-01-21T15:01:04.000000Z",
            'deleted_at' => Carbon::now(),
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $game->ID, 't' => 1]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'Count' => 2,
            'Total' => 2,
            'Results' => [
                [
                    'User' => $user1->User,
                    'ULID' => $user1->ulid,
                    'Submitted' => $comment1->created_at->toISOString(),
                    'CommentText' => $comment1->body,
                ],
                [
                    'User' => $user2->User,
                    'ULID' => $user2->ulid,
                    'Submitted' => $comment2->created_at->toISOString(),
                    'CommentText' => $comment2->body,
                ],
            ],
        ]);
    }

    public function testGetCommentsForUserByName(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);

        $comment1 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::User,
            'user_id' => $user2->ID,
            'body' => 'This is my first comment.',
            'created_at' => "2024-01-18T15:01:04.000000Z",
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::User,
            'user_id' => $user2->ID,
            'body' => 'This is my second comment.',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);
        $comment3 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $bannedUser->ID,
            'body' => 'This comment is from a banned user!',
            'created_at' => "2024-01-20T15:01:04.000000Z",
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $user->User, 't' => 3]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'Count' => 2,
            'Total' => 2,
            'Results' => [
                [
                    'User' => $user2->User,
                    'ULID' => $user2->ulid,
                    'Submitted' => $comment1->created_at->toISOString(),
                    'CommentText' => $comment1->body,
                ],
                [
                    'User' => $user2->User,
                    'ULID' => $user2->ulid,
                    'Submitted' => $comment2->created_at->toISOString(),
                    'CommentText' => $comment2->body,
                ],
            ],
        ]);
    }

    public function testGetCommentsForUserByUlid(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);

        $comment1 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::User,
            'user_id' => $user2->ID,
            'body' => 'This is my first comment.',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::User,
            'user_id' => $user2->ID,
            'body' => 'This is my second comment.',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);
        $comment3 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $bannedUser->ID,
            'body' => 'This comment is from a banned user!',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $user->ulid, 't' => 3]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'Count' => 2,
            'Total' => 2,
            'Results' => [
                [
                    'User' => $user2->User,
                    'ULID' => $user2->ulid,
                    'Submitted' => $comment1->created_at->toISOString(),
                    'CommentText' => $comment1->body,
                ],
                [
                    'User' => $user2->User,
                    'ULID' => $user2->ulid,
                    'Submitted' => $comment2->created_at->toISOString(),
                    'CommentText' => $comment2->body,
                ],
            ],
        ]);
    }

    public function testGetCommentsForUserWithDisabledWall(): void
    {
        // Arrange
        $user = User::factory()->create(['UserWallActive' => false]);
        $user2 = User::factory()->create();
        $comment1 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::User,
            'user_id' => $user2->ID,
            'body' => 'This is my first comment.',
            'created_at' => "2024-01-18T15:01:04.000000Z",
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $user->ID,
            'commentable_type' => CommentableType::User,
            'user_id' => $user2->ID,
            'body' => 'This is my second comment.',
            'created_at' => "2024-01-19T15:01:04.000000Z",
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $user->User]));

        // Assert
        $response->assertNotFound();
        $response->assertJson([]);
    }
}
