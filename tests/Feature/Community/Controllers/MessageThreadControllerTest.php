<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessageThreadControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0]);
    }

    private function createMessageThreadWithParticipant(User $participant): MessageThread
    {
        $thread = MessageThread::factory()->create();

        $message = Message::factory()->create([
            'thread_id' => $thread->id,
            'author_id' => $participant->id,
        ]);

        $thread->update([
            'last_message_id' => $message->id,
        ]);

        MessageThreadParticipant::create([
            'thread_id' => $thread->id,
            'user_id' => $participant->id,
            'num_unread' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $thread->fresh();
    }

    public function testIndexReturnsCorrectProps(): void
    {
        // Arrange
        $thread = $this->createMessageThreadWithParticipant($this->user);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('message-thread.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('paginatedMessageThreads.total', 1)
            ->has('paginatedMessageThreads.items', 1)
            ->where('paginatedMessageThreads.items.0.id', $thread->id)
            ->where('paginatedMessageThreads.items.0.title', $thread->title)
            ->has('paginatedMessageThreads.items.0.lastMessage.createdAt')
            ->where('paginatedMessageThreads.items.0.isUnread', false)
            ->has('paginatedMessageThreads.items.0.messages')
            ->has('paginatedMessageThreads.items.0.participants')
            ->where('unreadMessageCount', 0)
        );
    }

    public function testShowPreservesMessageQueryParameterDuringRedirect(): void
    {
        // Arrange
        $this->actingAs($this->user);
        $thread = $this->createMessageThreadWithParticipant($this->user);

        // ... create 25 messages to force multiple pages ...
        for ($i = 0; $i < 24; $i++) {
            Message::factory()->create([
                'thread_id' => $thread->id,
                'author_id' => $this->user->id,
            ]);
        }

        // Act
        $response = $this->get(route('message-thread.show', [
            'messageThread' => $thread->id,
            'message' => '12345', // !!
        ]));

        // Assert
        $response->assertRedirect(route('message-thread.show', [
            'messageThread' => $thread->id,
            'page' => 2,
            'message' => '12345', // !!
        ]));
    }

    public function testShowAutoRedirectIncludesNewestMessageId(): void
    {
        // Arrange
        $this->actingAs($this->user);
        $thread = $this->createMessageThreadWithParticipant($this->user);

        // ... create 25 messages to force multiple pages ...
        $messages = [];
        for ($i = 0; $i < 24; $i++) {
            $messages[] = Message::factory()->create([
                'thread_id' => $thread->id,
                'author_id' => $this->user->id,
                'created_at' => now()->addMinutes($i),
            ]);
        }

        $newestMessage = end($messages);

        // Act
        $response = $this->get(route('message-thread.show', [
            'messageThread' => $thread->id,
            // !! no message id
        ]));

        // Assert
        $response->assertRedirect(route('message-thread.show', [
            'messageThread' => $thread->id,
            'page' => 2,
            'message' => $newestMessage->id, // !!
        ]));
    }
}
