<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildMessageThreadShowPagePropsAction;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildMessageThreadShowPagePropsActionTest extends TestCase
{
    use RefreshDatabase;

    private BuildMessageThreadShowPagePropsAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new BuildMessageThreadShowPagePropsAction();
    }

    public function testItHandlesBasicThreadWithSingleMessage(): void
    {
        // Arrange
        $user = User::factory()->create();
        $thread = MessageThread::factory()->create([
            'title' => 'Test Thread',
        ]);
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
        Message::factory()->create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
            'body' => 'Test message content.',
        ]);

        // Act
        $result = $this->action->execute($thread, $user);

        // Assert
        $this->assertNotNull($result['props']);
        $this->assertNull($result['redirectToPage']);
        $this->assertEquals('Test Thread', $result['props']->messageThread->title);
        $this->assertEquals(1, $result['props']->paginatedMessages->total);
        $this->assertTrue($result['props']->canReply);
    }

    public function testItRedirectsWhenRequestedPageExceedsLastPage(): void
    {
        // Arrange
        $user = User::factory()->create();
        $thread = MessageThread::factory()->create();
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
        Message::factory()->create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
        ]);

        // Act
        $result = $this->action->execute($thread, $user, currentPage: 5, perPage: 20);

        // Assert
        $this->assertNull($result['props']);
        $this->assertEquals(1, $result['redirectToPage']);
    }

    public function testItConvertsUserIdShortcodesInMessageBodies(): void
    {
        // Arrange
        $user = User::factory()->create(['display_name' => 'TestUser']);
        $thread = MessageThread::factory()->create();
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
        Message::factory()->create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
            'body' => "Hello [user={$user->id}]!",
        ]);

        // Act
        $result = $this->action->execute($thread, $user);

        // Assert
        $this->assertNotNull($result['props']);
        $this->assertEquals(
            'Hello [user=TestUser]!',
            $result['props']->paginatedMessages->items[0]->body
        );
    }

    public function testItExtractsAndFetchesDynamicEntities(): void
    {
        // Arrange
        $user = User::factory()->create(['display_name' => 'TestUser']);
        $thread = MessageThread::factory()->create();
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
        Message::factory()->create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
            'body' => "Hello [user={$user->id}], check [game=123]!",
        ]);

        // Act
        $result = $this->action->execute($thread, $user);

        // Assert
        $this->assertNotNull($result['props']);
        $this->assertCount(1, $result['props']->dynamicEntities->users);
        $this->assertEquals('TestUser', $result['props']->dynamicEntities->users[0]['displayName']);
    }

    public function testItDisallowsReplyWhenAllOtherParticipantsAreDeleted(): void
    {
        // Arrange
        $activeUser = User::factory()->create();
        $deletedUser = User::factory()->create(['Deleted' => now()]);
        $thread = MessageThread::factory()->create();

        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $activeUser->id,
        ]);
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $deletedUser->id,
        ]);

        // Act
        $result = $this->action->execute($thread, $activeUser);

        // Assert
        $this->assertNotNull($result['props']);
        $this->assertFalse($result['props']->canReply);
    }

    public function testItPaginatesMessagesCorrectly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $thread = MessageThread::factory()->create();
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        // ... create 25 messages (more than one page) ...
        Message::factory()->count(25)->create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
        ]);

        // Act
        $result = $this->action->execute($thread, $user, perPage: 20);

        // Assert
        $this->assertNotNull($result['props']);
        $this->assertEquals(25, $result['props']->paginatedMessages->total);
        $this->assertEquals(2, $result['props']->paginatedMessages->lastPage);
        $this->assertEquals(20, count($result['props']->paginatedMessages->items));
    }

    public function testItAllowsReplyInOneOnOneThread(): void
    {
        // Arrange
        $user = User::factory()->create();
        $thread = MessageThread::factory()->create();
        MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        // Act
        $result = $this->action->execute($thread, $user);

        // Assert
        $this->assertNotNull($result['props']);
        $this->assertTrue($result['props']->canReply);
    }

    public function testItMarksThreadAsReadOnLastPage(): void
    {
        // Arrange
        $user = User::factory()->create();
        $thread = MessageThread::factory()->create();
        $participant = MessageThreadParticipant::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'num_unread' => 5,
        ]);
        Message::factory()->create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
        ]);

        // Act
        $this->action->execute($thread, $user, currentPage: 1);

        // Assert
        $this->assertEquals(0, $participant->fresh()->num_unread);
    }
}
