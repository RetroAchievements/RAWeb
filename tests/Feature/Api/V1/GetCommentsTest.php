<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Api\V1\BootstrapsApiV1;
use Tests\TestCase;

class API_GetCommentsTest extends TestCase
{
    use RefreshDatabase; use WithFaker;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetComments'))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserCompletionProgressUnknownUser(): void
    {
        $this->get($this->apiUrl('GetComments', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetCommentsForAchievement(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

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

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $achievement->ID, 't' => 2]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'Count',
            'Total',
            'Results' => [
                '*' => [
                    'User',
                    'Submitted',
                    'CommentText',
                ],
            ],
        ]);
        $this->assertCount(2, $response->json('Results'));
        $this->assertEquals($user1->User, $response->json('Results.0.User'));
        $this->assertEquals($comment1->Payload, $response->json('Results.0.CommentText'));
        $this->assertEquals($user2->User, $response->json('Results.1.User'));
        $this->assertEquals($comment2->Payload, $response->json('Results.1.CommentText'));
    }

    public function testGetCommentsForGame(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
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

        // Act
        $response = $this->get($this->apiUrl('GetComments', ['i' => $game->ID, 't' => 1]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'Count',
            'Total',
            'Results' => [
                '*' => [
                    'User',
                    'Submitted',
                    'CommentText',
                ],
            ],
        ]);
        $this->assertCount(2, $response->json('Results'));
        $this->assertEquals($user1->username, $response->json('Results.0.User'));
        $this->assertEquals($comment1->Payload, $response->json('Results.0.CommentText'));
        $this->assertEquals($user2->username, $response->json('Results.1.User'));
        $this->assertEquals($comment2->Payload, $response->json('Results.1.CommentText'));
    }

    public function testGetCommentsForUser(): void
    {
        // Arrange
        $user = User::factory()->create();
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
        $response = $this->get($this->apiUrl('GetComments', ['u' => $user->User, 't' => 3]))
            ->assertSuccessful();

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'Count',
            'Total',
            'Results' => [
                '*' => [
                    'User',
                    'Submitted',
                    'CommentText',
                ],
            ],
        ]);
        $this->assertCount(2, $response->json('Results'));
        $this->assertEquals($user2->User, $response->json('Results.0.User'));
        $this->assertEquals($comment1->Payload, $response->json('Results.0.CommentText'));
        $this->assertEquals($user2->User, $response->json('Results.1.User'));
        $this->assertEquals($comment2->Payload, $response->json('Results.1.CommentText'));
    }
}
