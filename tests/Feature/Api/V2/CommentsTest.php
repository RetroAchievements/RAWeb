<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Enums\CommentableType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $game = $this->createGame();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->get("/api/v2/games/{$game->id}/comments");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesGameComments(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();
        $author = User::factory()->create([
            'display_name' => 'CommentAuthor',
            'username' => 'CommentAuthor',
        ]);

        $comment1 = Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
            'body' => 'This game is excellent.',
            'created_at' => '2024-01-18 15:01:04',
        ]);
        $comment2 = Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
            'body' => 'Still excellent.',
            'created_at' => '2024-01-19 15:01:04',
        ]);
        Comment::factory()->create([
            'commentable_id' => $game->id + 1,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments");

        // Assert
        $response->assertSuccessful();

        $this->assertEquals([
            (string) $comment1->id,
            (string) $comment2->id,
        ], collect($response->json('data'))->pluck('id')->all());

        $this->assertEquals('This game is excellent.', $response->json('data.0.attributes.body'));
        $this->assertEquals($author->avatarUrl, $response->json('data.0.attributes.authorAvatarUrl'));
        $this->assertEquals('CommentAuthor', $response->json('data.0.attributes.authorDisplayName'));
        $this->assertEquals($author->ulid, $response->json('data.0.attributes.authorId'));
        $this->assertFalse($response->json('data.0.attributes.isAutomated'));
        $this->assertEquals(route('comment.show', ['comment' => $comment1->id]), $response->json('data.0.attributes.permalink'));
        $this->assertArrayHasKey('submittedAt', $response->json('data.0.attributes'));
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItFetchesAchievementComments(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $achievement = $this->createAchievement();
        $author = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_id' => $achievement->id,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $author->id,
            'body' => 'This achievement is fair.',
        ]);
        Comment::factory()->create([
            'commentable_id' => $achievement->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/comments");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'comments', 'id' => (string) $comment->id],
        ]);
        $this->assertCount(1, $response->json('data'));
    }

    public function testItFetchesUserWallComments(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $wallOwner = User::factory()->create(['is_user_wall_active' => true]);
        $author = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_id' => $wallOwner->id,
            'commentable_type' => CommentableType::User,
            'user_id' => $author->id,
            'body' => 'Thanks for the help.',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$wallOwner->ulid}/wall-comments");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'comments', 'id' => (string) $comment->id],
        ]);
    }

    public function testItFetchesUserWallCommentRelationshipLinkage(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $wallOwner = User::factory()->create(['is_user_wall_active' => true]);
        $author = User::factory()->create();

        $comment = Comment::factory()->create([
            'commentable_id' => $wallOwner->id,
            'commentable_type' => CommentableType::User,
            'user_id' => $author->id,
            'body' => 'Thanks for the help.',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$wallOwner->ulid}/relationships/wall-comments");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals([
            ['type' => 'comments', 'id' => (string) $comment->id],
        ], $response->json('data'));
    }

    public function testItIncludesAuthorWhenRequested(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();
        $author = User::factory()->create([
            'display_name' => 'CommentAuthor',
            'motto' => 'Do not include this',
        ]);

        Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments?include=author&fields[users]=displayName,avatarUrl");

        // Assert
        $response->assertSuccessful();

        $this->assertEquals('users', $response->json('data.0.relationships.author.data.type'));
        $this->assertEquals($author->ulid, $response->json('data.0.relationships.author.data.id'));

        $includedAuthor = collect($response->json('included'))->firstWhere('type', 'users');
        $this->assertEquals('CommentAuthor', $includedAuthor['attributes']['displayName']);
        $this->assertArrayHasKey('avatarUrl', $includedAuthor['attributes']);
        $this->assertArrayNotHasKey('motto', $includedAuthor['attributes']);
    }

    public function testItDoesNotIncludeAuthorRelationshipByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();
        $author = User::factory()->create();

        Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('relationships', $response->json('data.0'));
        $this->assertArrayNotHasKey('included', $response->json());
    }

    public function testItSortsCommentsDescendingWhenRequested(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();
        $author = User::factory()->create();

        $olderComment = Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
            'created_at' => '2024-01-18 15:01:04',
        ]);
        $newerComment = Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
            'created_at' => '2024-01-19 15:01:04',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments?sort=-submittedAt");

        // Assert
        $response->assertSuccessful();

        $this->assertEquals([
            (string) $newerComment->id,
            (string) $olderComment->id,
        ], collect($response->json('data'))->pluck('id')->all());
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();
        $author = User::factory()->create();

        Comment::factory()->count(75)->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(75, $response->json('meta.page.total'));
    }

    public function testItExcludesDeletedCommentsAndCommentsFromBannedUsers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();
        $author = User::factory()->create();
        $bannedAuthor = User::factory()->create(['banned_at' => now()]);
        $deletedAuthor = User::factory()->create(['deleted_at' => now()]);

        $visibleComment = Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
        ]);
        Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $bannedAuthor->id,
        ]);
        Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $deletedAuthor->id,
        ]);
        Comment::factory()->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $author->id,
            'deleted_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'comments', 'id' => (string) $visibleComment->id],
        ]);
        $this->assertCount(1, $response->json('data'));
    }

    public function testItReturns404ForDisabledUserWall(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $wallOwner = User::factory()->create(['is_user_wall_active' => false]);
        $author = User::factory()->create();

        Comment::factory()->create([
            'commentable_id' => $wallOwner->id,
            'commentable_type' => CommentableType::User,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$wallOwner->ulid}/wall-comments");

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404ForDisabledUserWallCommentRelationshipLinkage(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $wallOwner = User::factory()->create(['is_user_wall_active' => false]);
        $author = User::factory()->create();

        Comment::factory()->create([
            'commentable_id' => $wallOwner->id,
            'commentable_type' => CommentableType::User,
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$wallOwner->ulid}/relationships/wall-comments");

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404ForMissingParent(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games/999999/comments');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns400ForInvalidSort(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments?sort=body");

        // Assert
        $response->assertStatus(400);
    }

    public function testItReturns400ForUnsupportedInclude(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = $this->createGame();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/comments?include=game");

        // Assert
        $response->assertStatus(400);
    }

    public function testItDoesNotExposeStandaloneCommentsIndex(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('comments')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/comments');

        // Assert
        $response->assertNotFound();
    }

    private function createGame(): Game
    {
        $system = System::factory()->create();

        return Game::factory()->create(['system_id' => $system->id]);
    }

    private function createAchievement(): Achievement
    {
        $game = $this->createGame();

        return Achievement::factory()->create(['game_id' => $game->id]);
    }
}
