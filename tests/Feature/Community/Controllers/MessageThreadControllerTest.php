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

        $this->user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
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
            ->where('paginatedMessageThreads.items.0.lastMessageId', $thread->last_message_id)
            ->where('paginatedMessageThreads.items.0.isUnread', false)
            ->has('paginatedMessageThreads.items.0.messages')
            ->has('paginatedMessageThreads.items.0.participants')
            ->where('unreadMessageCount', 0)
        );
    }
}
