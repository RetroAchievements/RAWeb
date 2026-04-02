<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Actions\AddToMessageThreadAction;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessagesTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'messages';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/messages';
    }

    protected function createResource(): Message
    {
        /** @var User $user1 */
        $user1 = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var User $user2 */
        $user2 = User::factory()->create();

        $thread = (new CreateMessageThreadAction())->execute($user1, $user2, $user1, 'Test Thread', 'Initial message.');
        return $thread->messages()->first();
    }

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $message = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->get("{$this->resourceEndpoint()}/{$message->id}");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItReturnsMessage(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $message = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->withHeader('X-API-Key', 'test-key')
            ->get("{$this->resourceEndpoint()}/{$message->id}");

        // Assert
        $response->assertFetchedOne([
            'type' => 'messages',
            'id' => (string) $message->id,
        ]);
    }

    public function testItPreventsDirectListing(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint());

        // Assert
        $response->assertStatus(404);
    }

    public function testItCreatesReplyMessage(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var User $other */
        $other = User::factory()->create();
        $thread = (new CreateMessageThreadAction())->execute($user, $other, $user, 'Thread', 'Initial');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'messages',
                    'attributes' => [
                        'body' => 'Reply message.',
                    ],
                    'relationships' => [
                        'messageThread' => [
                            'data' => [
                                'type' => 'message-threads',
                                'id' => (string) $thread->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->post($this->resourceEndpoint());

        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('messages', [
            'body' => 'Reply message.',
            'thread_id' => $thread->id,
            'author_id' => $user->id,
        ]);
    }

    public function testItRejectsReplyToNonParticipantThread(): void
    {
        // Arrange
        /** @var User $user1 */
        $user1 = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();

        $thread = (new CreateMessageThreadAction())->execute($user2, $user3, $user2, 'Thread', 'Initial');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'messages',
                    'attributes' => [
                        'body' => 'Unauthorized reply.',
                    ],
                    'relationships' => [
                        'messageThread' => [
                            'data' => [
                                'type' => 'message-threads',
                                'id' => (string) $thread->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->post($this->resourceEndpoint());

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'You can only reply to threads you are a participant in.');
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $message = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->withHeader('X-API-Key', 'test-key')
            ->get("{$this->resourceEndpoint()}/{$message->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Initial message.', $attributes['body']);
        $this->assertArrayHasKey('createdAt', $attributes);
    }

    public function testItIncludesAuthorRelationship(): void
    {
        // Arrange
        $message = $this->createResource();

        // Get the actual author of the message
        $author = $message->author;

        // Act
        $response = $this->jsonApi('v2')
            ->expects('messages')
            ->withHeader('X-API-Key', 'test-key')
            ->includePaths('author')
            ->get("{$this->resourceEndpoint()}/{$message->id}");

        // Assert
        $response->assertSuccessful();
        $relationships = $response->json('data.relationships');

        $this->assertArrayHasKey('author', $relationships);
        $this->assertEquals('users', $relationships['author']['data']['type']);
        $this->assertEquals($author->ulid, $relationships['author']['data']['id']);
    }
}
