<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\V1\BootstrapsApiV1;
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
            'ArticleID' => $achievement->ID,
            'ArticleType' => 2,
            'user_id' => $user1->ID,
            'Payload' => 'This is a great achievement!',
        ]);
        $comment2 = Comment::factory()->create([
            'ArticleID' => $achievement->ID,
            'ArticleType' => 2,
            'user_id' => $user2->ID,
            'Payload' => 'I agree, this is awesome!',
        ]);
        $comment3 = Comment::factory()->create([
            'ArticleID' => $achievement->ID,
            'ArticleType' => 2,
            'user_id' => $bannedUser->ID,
            'Payload' => 'This comment is from a banned user!',
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
                    'Submitted' => $comment1->Submitted->toISOString(),
                    'CommentText' => $comment1->Payload,
                ],
                [
                    'User' => $user2->User,
                    'Submitted' => $comment2->Submitted->toISOString(),
                    'CommentText' => $comment2->Payload,
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
            'ArticleID' => $game->ID,
            'ArticleType' => 1,
            'user_id' => $user1->ID,
            'Payload' => 'This is a great achievement!',
        ]);
        $comment2 = Comment::factory()->create([
            'ArticleID' => $game->ID,
            'ArticleType' => 1,
            'user_id' => $user2->ID,
            'Payload' => 'I agree, this is awesome!',
        ]);
        $comment3 = Comment::factory()->create([
            'ArticleID' => $game->ID,
            'ArticleType' => 2,
            'user_id' => $bannedUser->ID,
            'Payload' => 'This comment is from a banned user!',
        ]);
        $deletedComment = Comment::factory()->create([
            'ArticleID' => $game->ID,
            'ArticleType' => 2,
            'user_id' => $user1->ID,
            'Payload' => 'This comment has been deleted!',
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
                    'Submitted' => $comment1->Submitted->toISOString(),
                    'CommentText' => $comment1->Payload,
                ],
                [
                    'User' => $user2->User,
                    'Submitted' => $comment2->Submitted->toISOString(),
                    'CommentText' => $comment2->Payload,
                ],
            ],
        ]);
    }

    public function testGetCommentsForUser(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);

        $comment1 = Comment::factory()->create([
            'ArticleID' => $user->ID,
            'ArticleType' => 3,
            'user_id' => $user2->ID,
            'Payload' => 'This is my first comment.',
        ]);
        $comment2 = Comment::factory()->create([
            'ArticleID' => $user->ID,
            'ArticleType' => 3,
            'user_id' => $user2->ID,
            'Payload' => 'This is my second comment.',
        ]);
        $comment3 = Comment::factory()->create([
            'ArticleID' => $user->ID,
            'ArticleType' => 2,
            'user_id' => $bannedUser->ID,
            'Payload' => 'This comment is from a banned user!',
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
                    'Submitted' => $comment1->Submitted->toISOString(),
                    'CommentText' => $comment1->Payload,
                ],
                [
                    'User' => $user2->User,
                    'Submitted' => $comment2->Submitted->toISOString(),
                    'CommentText' => $comment2->Payload,
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
            'ArticleID' => $user->ID,
            'ArticleType' => 3,
            'user_id' => $user2->ID,
            'Payload' => 'This is my first comment.',
        ]);
        $comment2 = Comment::factory()->create([
            'ArticleID' => $user->ID,
            'ArticleType' => 3,
            'user_id' => $user2->ID,
            'Payload' => 'This is my second comment.',
        ]);

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $user->User]))
            ->assertNotFound()
            ->assertJson([]);
    }
}
