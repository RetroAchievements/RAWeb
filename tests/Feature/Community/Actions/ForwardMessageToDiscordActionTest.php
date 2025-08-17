<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\ForwardMessageToDiscordAction;
use App\Models\DiscordMessageThreadMapping;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardMessageToDiscordActionTest extends TestCase
{
    use RefreshDatabase;

    private MockHandler $mockHandler;
    private array $webhookHistory = [];
    private ForwardMessageToDiscordAction $action;
    private User $sender;
    private User $recipient;
    private MessageThread $thread;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a mock Guzzle client to capture webhook payloads.
        // We don't want to actually try to call Discord's API.
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware to capture requests.
        $history = Middleware::history($this->webhookHistory);
        $handlerStack->push($history);

        $mockClient = new Client(['handler' => $handlerStack]);

        // Create the action with mocked client.
        $this->action = new ForwardMessageToDiscordAction($mockClient);

        // Create test users and thread.
        $this->sender = User::factory()->create(['User' => 'TestSender']);
        $this->recipient = User::factory()->create(['User' => 'TestRecipient']);
        $this->thread = MessageThread::factory()->create(['title' => 'Test Thread']);
    }

    /**
     * Create a new message from $this->sender that's associated with $this->thread.
     */
    private function createMessage(string $body): Message
    {
        return Message::factory()->create([
            'thread_id' => $this->thread->id,
            'author_id' => $this->sender->id,
            'body' => $body,
        ]);
    }

    /**
     * Set up the Discord webhook config for a specific user.
     */
    private function setDiscordConfig(
        User $user,
        string $webhookUrl = 'https://discord.com/api/webhooks/123/abc',
        bool $isForum = false,
        ?string $verifyUrl = null,
        ?string $achievementIssuesUrl = null
    ): void {
        config([
            'services.discord.inbox_webhook.' . $user->username => [
                'url' => $webhookUrl,
                'is_forum' => $isForum,
                'verify_url' => $verifyUrl,
                'achievement_issues_url' => $achievementIssuesUrl,
            ],
        ]);
    }

    private function queueDiscordResponses(int $count = 1, array $responseData = []): void
    {
        $defaultResponse = [
            'id' => '123456789',
            'channel_id' => 'thread_123',
        ];

        for ($i = 0; $i < $count; $i++) {
            $this->mockHandler->append(
                new Response(200, [], json_encode(array_merge($defaultResponse, $responseData)))
            );
        }
    }

    private function getLastWebhookPayload(): ?array
    {
        if (empty($this->webhookHistory)) {
            return null;
        }

        $lastRequest = end($this->webhookHistory)['request'];

        return json_decode($lastRequest->getBody()->getContents(), true);
    }

    private function getAllWebhookPayloads(): array
    {
        $payloads = [];
        foreach ($this->webhookHistory as $transaction) {
            $payloads[] = json_decode($transaction['request']->getBody()->getContents(), true);
        }

        return $payloads;
    }

    public function testItDoesNothingWhenNoWebhookIsConfigured(): void
    {
        // Arrange
        $message = $this->createMessage('Test message');
        // ... no Discord config is set...

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(0, $this->webhookHistory);
    }

    public function testItDoesNothingWhenTheThreadTitleIsEmpty(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient);
        $this->thread->title = ''; // !! this is an edge case, shouldn't happen in real life
        $this->thread->save();
        $message = $this->createMessage('Test message');

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(0, $this->webhookHistory);
    }

    public function testItDoesNothingWhenTheMessageBodyIsEmpty(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient);
        $message = $this->createMessage(''); // !! this is an edge case, shouldn't happen in real life

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(0, $this->webhookHistory);
    }

    public function testItSendsSimpleMessageToNonForumChannel(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: false);
        $message = $this->createMessage('This is a test message');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $payload = $this->getLastWebhookPayload();
        $this->assertEquals($this->recipient->username . ' Inbox', $payload['username']);
        $this->assertCount(1, $payload['embeds']);
        $this->assertEquals('This is a test message', $payload['embeds'][0]['description']);
        $this->assertEquals($this->sender->display_name, $payload['embeds'][0]['author']['name']);
        $this->assertEquals($this->thread->title, $payload['embeds'][0]['title']);
    }

    public function testItTruncatesLongMessagesForNonForumChannels(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: false);
        $message = $this->createMessage(str_repeat('A', 3000));
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $payload = $this->getLastWebhookPayload();
        $this->assertEquals(2000, mb_strlen($payload['embeds'][0]['description'])); // !! truncated to 2000 chars
        $this->assertEquals(str_repeat('A', 2000), $payload['embeds'][0]['description']);
    }

    public function testItCreatesNewThreadForForumChannels(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $message = $this->createMessage('First message to forum');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $request = $this->webhookHistory[0]['request'];
        $this->assertStringContainsString('wait=true', $request->getUri()->getQuery());

        $payload = $this->getLastWebhookPayload();
        $this->assertArrayHasKey('thread_name', $payload);
        $this->assertEquals($this->thread->title, $payload['thread_name']);
        $this->assertEquals('First message to forum', $payload['embeds'][0]['description']);
    }

    public function testItStoresDiscordThreadMappingsOnThreadCreate(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $message = $this->createMessage('Create thread message');
        $this->queueDiscordResponses(1, ['channel_id' => 'discord_thread_123']);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $mapping = DiscordMessageThreadMapping::findMapping($this->thread->id, $this->recipient->id);
        $this->assertNotNull($mapping);
        $this->assertEquals('discord_thread_123', $mapping->discord_thread_id);
        $this->assertEquals($this->thread->id, $mapping->message_thread_id);
        $this->assertEquals($this->recipient->ID, $mapping->recipient_id);
    }

    public function testItUsesExistingThreadsForReplies(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);

        // ... store an existing thread mapping ...
        DiscordMessageThreadMapping::storeMapping(
            $this->thread->id,
            $this->recipient->ID,
            'existing_thread_456'
        );

        $message = $this->createMessage('Reply to existing thread');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $request = $this->webhookHistory[0]['request'];
        $this->assertStringContainsString('thread_id=existing_thread_456', $request->getUri()->getQuery());

        $payload = $this->getLastWebhookPayload();
        $this->assertArrayNotHasKey('thread_name', $payload); // !! no thread_name for existing threads
    }

    public function testItChunksLongMessagesInForumChannels(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $message = $this->createMessage(str_repeat('B', 4500)); // !! 4500 chars = 3 chunks
        $this->queueDiscordResponses(3); // !! 1 for thread creation + 2 for additional chunks

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(3, $this->webhookHistory);

        $payloads = $this->getAllWebhookPayloads();

        // ... the first payload creates the Discord thread with the first chunk ...
        $this->assertArrayHasKey('thread_name', $payloads[0]);
        $this->assertEquals('[Part 1 of 3]', $payloads[0]['content']);
        $this->assertEquals(2000, mb_strlen($payloads[0]['embeds'][0]['description']));

        // ... the second payload is part 2 ...
        $this->assertEquals('[Part 2 of 3]', $payloads[1]['content']);
        $this->assertEquals(2000, mb_strlen($payloads[1]['embeds'][0]['description']));

        // ... the third payload is part 3 ...
        $this->assertEquals('[Part 3 of 3]', $payloads[2]['content']);
        $this->assertEquals(500, mb_strlen($payloads[2]['embeds'][0]['description']));
    }

    public function testItRoutesVerificationMessagesToTheVerifyUrl(): void
    {
        // Arrange
        $this->setDiscordConfig(
            $this->recipient,
            verifyUrl: 'https://discord.com/api/webhooks/verify/xyz'
        );
        $this->thread->title = 'Discord Verification Request';
        $this->thread->save();
        $message = $this->createMessage('Please verify my Discord account');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $request = $this->webhookHistory[0]['request'];
        $this->assertEquals('https://discord.com/api/webhooks/verify/xyz', (string) $request->getUri());

        $payload = $this->getLastWebhookPayload();
        $this->assertEquals(hexdec('0x00CC66'), $payload['embeds'][0]['color']);
    }

    public function testItHandlesAchievementIssueMessagesWithStructuredTitle(): void
    {
        // Arrange
        $this->setDiscordConfig(
            $this->recipient,
            achievementIssuesUrl: 'https://discord.com/api/webhooks/issues/def',
            isForum: true
        );
        $this->thread->title = 'Issue: That Was Easy [12345] (Sonic the Hedgehog)';
        $this->thread->save();
        $message = $this->createMessage('The achievement is not triggering properly');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $request = $this->webhookHistory[0]['request'];
        $uri = (string) $request->getUri();
        $this->assertStringStartsWith('https://discord.com/api/webhooks/issues/def', $uri);

        $payload = $this->getLastWebhookPayload();
        $this->assertEquals('12345: That Was Easy (Sonic the Hedgehog)', $payload['thread_name']); // !! reformatted
        $this->assertStringContainsString('achievement/12345', $payload['embeds'][0]['description']); // !! achievement URL added
    }

    public function testItDoesNotAddAchievementUrlToReplies(): void
    {
        // Arrange
        $this->setDiscordConfig(
            $this->recipient,
            achievementIssuesUrl: 'https://discord.com/api/webhooks/issues/def',
            isForum: true
        );

        // ... store an existing thread mapping for to handle a reply scenario ...
        DiscordMessageThreadMapping::storeMapping(
            $this->thread->id,
            $this->recipient->ID,
            'existing_issue_thread'
        );

        $this->thread->title = 'Issue: That Was Easy [12345] (Sonic the Hedgehog)';
        $this->thread->save();
        $message = $this->createMessage('Following up on this issue');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $payload = $this->getLastWebhookPayload();
        $this->assertStringNotContainsString('achievement/12345', $payload['embeds'][0]['description']); // !! no URL in reply
        $this->assertEquals('Following up on this issue', $payload['embeds'][0]['description']);
    }

    public function testItHandlesExactly2000CharacterMessage(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $exactMessage = str_repeat('X', 2000); // !!
        $message = $this->createMessage($exactMessage);
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $payload = $this->getLastWebhookPayload();
        $this->assertArrayNotHasKey('content', $payload);
        $this->assertEquals(2000, mb_strlen($payload['embeds'][0]['description']));
    }

    public function testItHandles2001CharacterMessageWithChunking(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $longMessage = str_repeat('Y', 2001); // !! 2001 chars triggers chunking
        $message = $this->createMessage($longMessage);
        $this->queueDiscordResponses(2);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(2, $this->webhookHistory);

        $payloads = $this->getAllWebhookPayloads();
        $this->assertEquals('[Part 1 of 2]', $payloads[0]['content']);
        $this->assertEquals('[Part 2 of 2]', $payloads[1]['content']);
        $this->assertEquals(1, mb_strlen($payloads[1]['embeds'][0]['description']));
    }

    public function testItHandlesMultipleRepliesInSameThread(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);

        // ... the first message creates a thread ...
        $message1 = $this->createMessage('First message');
        $this->queueDiscordResponses(1, ['channel_id' => 'thread_789']);
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message1);

        // ... clear the webhook history to prepare for the second message ...
        $this->webhookHistory = [];

        // ... the second message should use the existing thread ...
        $message2 = $this->createMessage('Second message');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message2);

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $request = $this->webhookHistory[0]['request'];
        $this->assertStringContainsString('thread_id=thread_789', $request->getUri()->getQuery());

        $payload = $this->getLastWebhookPayload();
        $this->assertEquals('Second message', $payload['embeds'][0]['description']);
    }

    public function testItTruncatesLongThreadTitlesToDiscordLimit(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $this->thread->title = str_repeat('Z', 150); // !!
        $this->thread->save();
        $message = $this->createMessage('Message with long title');
        $this->queueDiscordResponses(1);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $payload = $this->getLastWebhookPayload();
        $this->assertEquals(100, mb_strlen($payload['thread_name'])); // !! truncated
        $this->assertEquals(str_repeat('Z', 100), $payload['thread_name']);
    }

    public function testItHandlesNullDiscordThreadIdInResponse(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);
        $message = $this->createMessage('Message without thread ID');
        $this->queueDiscordResponses(1, ['channel_id' => null]);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $mapping = DiscordMessageThreadMapping::findMapping($this->thread->id, $this->recipient->id);
        $this->assertNull($mapping); // !! no mapping is stored when thread ID is null, but at least we don't crash
    }

    public function testItHandlesMultipleChunksForExistingThread(): void
    {
        // Arrange
        $this->setDiscordConfig($this->recipient, isForum: true);

        // ... store an existing thread mapping ...
        DiscordMessageThreadMapping::storeMapping(
            $this->thread->id,
            $this->recipient->ID,
            'existing_thread_multi'
        );

        $longMessage = str_repeat('M', 4500); // !! 3 chunks
        $message = $this->createMessage($longMessage);
        $this->queueDiscordResponses(3);

        // Act
        $this->action->execute($this->sender, $this->recipient, $this->thread, $message);

        // Assert
        $this->assertCount(3, $this->webhookHistory);

        // ... all requests should use the thread_id parameter ...
        foreach ($this->webhookHistory as $transaction) {
            $request = $transaction['request'];
            $this->assertStringContainsString('thread_id=existing_thread_multi', $request->getUri()->getQuery());
        }

        $payloads = $this->getAllWebhookPayloads();
        $this->assertEquals('[Part 1 of 3]', $payloads[0]['content']);
        $this->assertEquals('[Part 2 of 3]', $payloads[1]['content']);
        $this->assertEquals('[Part 3 of 3]', $payloads[2]['content']);
    }
}
