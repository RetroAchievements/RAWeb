<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Actions\DeleteMessageThreadAction;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class MessageThreadsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'message-threads';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/message-threads';
    }

    protected function createResource(): MessageThread
    {
        /** @var User $user1 */
        // Find existing user with wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6, or create one if it doesn't exist
        $user1 = User::where('web_api_key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')->first()
            ?? User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $user2 */
        $user2 = User::factory()->create();

        return (new CreateMessageThreadAction())->execute(
            $user1,
            $user2,
            $user1,
            'Test Thread',
            'Test message body.'
        );
    }

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->get($this->resourceEndpoint());

        // Assert
        $response->assertUnauthorized();
    }

    public function testItListsMessageThreads(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $thread = (new CreateMessageThreadAction())->execute(
            $user,
            $otherUser,
            $user,
            'Test Thread',
            'Test message body.'
        );

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint());

        // Assert
        $response->assertFetchedMany([
            ['type' => 'message-threads', 'id' => (string) $thread->id],
        ]);
    }

    public function testItOnlyReturnsThreadsForAuthenticatedUser(): void
    {
        // Arrange
        /** @var User $user1 */
        $user1 = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $user2 */
        $user2 = User::factory()->create(['web_api_key' => 'other-key']);

        $thread1 = (new CreateMessageThreadAction())->execute($user1, $user2, $user1, 'Thread 1', 'Body 1');
        $thread2 = (new CreateMessageThreadAction())->execute($user2, $user1, $user2, 'Thread 2', 'Body 2');

        // Act - user1 should see both threads (as sender of thread1, recipient of thread2)
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint());

        // Assert - user1 should see both threads they're participating in
        $response->assertFetchedMany([
            ['type' => 'message-threads', 'id' => (string) $thread1->id],
            ['type' => 'message-threads', 'id' => (string) $thread2->id],
        ]);

        // Act - user2 should also see both threads
        $response2 = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'other-key')
            ->get($this->resourceEndpoint());

        // Assert - user2 should see both threads they're participating in
        $response2->assertFetchedMany([
            ['type' => 'message-threads', 'id' => (string) $thread1->id],
            ['type' => 'message-threads', 'id' => (string) $thread2->id],
        ]);
    }

    public function testItFiltersByUnread(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $other */
        $other = User::factory()->create();

        $readThread = (new CreateMessageThreadAction())->execute($user, $other, $user, 'Read Thread', 'Body');
        $unreadThread = (new CreateMessageThreadAction())->execute($other, $user, $other, 'Unread Thread', 'Body');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint() . '?filter[unread]=true');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'message-threads', 'id' => (string) $unreadThread->id],
        ]);
        $response->assertJsonMissing(['id' => (string) $readThread->id]);
    }

    public function testItCreatesMessageThread(): void
    {
        // Arrange
        /** @var User $sender */
        $sender = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $recipient */
        $recipient = User::factory()->create();

        // Act - Use Laravel's json method with proper headers
        $response = $this->json('POST', $this->resourceEndpoint(), [
            'data' => [
                'type' => 'message-threads',
                'attributes' => [
                    'title' => 'New Thread Title',
                    'body' => 'New thread body.',
                ],
                'relationships' => [
                    'recipient' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $recipient->ulid,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'X-API-Key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6',
        ]);

        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('message_threads', [
            'title' => 'New Thread Title',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'New thread body.',
            'author_id' => $sender->id,
        ]);
    }

    public function testItRejectsMessageToSelf(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'message-threads',
                    'attributes' => [
                        'title' => 'Self Message',
                        'body' => 'Message to self.',
                    ],
                    'relationships' => [
                        'recipient' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $user->ulid,
                            ],
                        ],
                    ],
                ],
            ])
            ->post($this->resourceEndpoint());

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'You cannot send a message to yourself.');
    }

    public function testItDeletesMessageThread(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $thread = (new CreateMessageThreadAction())->execute(
            $user,
            $otherUser,
            $user,
            'Test Thread',
            'Test message body.'
        );

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->delete("{$this->resourceEndpoint()}/{$thread->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'deleted_at' => now(),
        ]);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $thread = (new CreateMessageThreadAction())->execute(
            $user,
            $otherUser,
            $user,
            'Test Thread',
            'Test message body.'
        );

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get("{$this->resourceEndpoint()}/{$thread->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Test Thread', $attributes['title']);
        $this->assertEquals(1, $attributes['numMessages']);
        $this->assertArrayHasKey('unreadCount', $attributes);
        $this->assertArrayHasKey('isUnread', $attributes);
    }

    public function testItSupportsPagination(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        // Create enough threads to trigger pagination
        for ($i = 0; $i < 30; $i++) {
            (new CreateMessageThreadAction())->execute(
                $user,
                $otherUser,
                $user,
                "Test Thread {$i}",
                "Test message body {$i}."
            );
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('message-threads')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint() . '?page[number]=1&page[size]=10');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(10, $response->json('data')); // 10 per page
    }
}
