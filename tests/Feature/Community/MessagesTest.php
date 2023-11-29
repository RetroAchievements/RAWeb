<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Community\Actions\AddToMessageThreadAction;
use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Actions\DeleteMessageThreadAction;
use App\Community\Actions\ReadMessageThreadAction;
use App\Community\Enums\UserRelationship;
use App\Community\Models\Message;
use App\Community\Models\UserRelation;
use App\Site\Actions\ClearAccountDataAction;
use App\Site\Enums\UserPreference;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Site\Concerns\TestsMail;
use Tests\TestCase;

class MessagesTest extends TestCase
{
    use RefreshDatabase;
    use TestsMail;

    public function testCreateMessageChain(): void
    {
        $now = Carbon::now()->floorSecond();
        Carbon::setTestNow($now);

        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create(['websitePrefs' => (1 << UserPreference::EmailOn_PrivateMessage)]);

        // user1 sends message to user2
        $this->captureEmails();
        $thread = (new CreateMessageThreadAction())->execute($user1, $user2, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 1,
            'last_message_id' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 1,
            'thread_id' => 1,
            'author_id' => $user1->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);

        $user1->refresh();
        $this->assertEquals(0, $user1->UnreadMessageCount);
        $user2->refresh();
        $this->assertEquals(1, $user2->UnreadMessageCount);

        $this->assertEmailSent($user2, "New Private Message from {$user1->User}");

        // user2 responds
        (new ReadMessageThreadAction())->execute($thread, $user2);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $user2->refresh();
        $this->assertEquals(0, $user2->UnreadMessageCount);

        $now2 = $now->clone()->addMinutes(5);
        Carbon::setTestNow($now2);

        $this->captureEmails();
        (new AddToMessageThreadAction())->execute($thread, $user2, 'This is a response.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 2,
            'last_message_id' => 2,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 2,
            'thread_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);

        $user1->refresh();
        $this->assertEquals(1, $user1->UnreadMessageCount);

        $this->assertEmailNotSent($user1);

        // user2 responds again
        $now3 = $now2->clone()->addMinutes(5);
        Carbon::setTestNow($now3);

        $this->captureEmails();
        (new AddToMessageThreadAction())->execute($thread, $user2, 'This is another response.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 3,
            'last_message_id' => 3,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 3,
            'thread_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is another response.',
            'created_at' => $now3,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 2,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);

        $user1->refresh();
        $this->assertEquals(2, $user1->UnreadMessageCount);

        $this->assertEmailNotSent($user1);

        // user1 responds
        (new ReadMessageThreadAction())->execute($thread, $user1);
        $user1->refresh();
        $this->assertEquals(0, $user1->UnreadMessageCount);

        $now4 = $now3->clone()->addMinutes(5);
        Carbon::setTestNow($now4);

        $this->captureEmails();
        (new AddToMessageThreadAction())->execute($thread, $user1, 'This is a third response.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 4,
            'last_message_id' => 4,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 4,
            'thread_id' => 1,
            'author_id' => $user1->ID,
            'body' => 'This is a third response.',
            'created_at' => $now4->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);

        $user2->refresh();
        $this->assertEquals(1, $user2->UnreadMessageCount);

        $this->assertEmailSent($user2, "New Private Message from {$user1->User}");

        // user1 deletes
        $now5 = $now4->clone()->addMinutes(5);
        Carbon::setTestNow($now5);

        $this->captureEmails();
        (new DeleteMessageThreadAction())->execute($thread, $user1);
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 4,
            'last_message_id' => 4,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => $now5->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);

        $user2->refresh();
        $this->assertEquals(1, $user2->UnreadMessageCount);

        $this->assertEmailNotSent($user1);
        $this->assertEmailNotSent($user2);

        // user2 deletes - when both users delete the message, it's removed from the DB
        $now6 = $now5->clone()->addMinutes(5);
        Carbon::setTestNow($now6);

        $this->captureEmails();
        (new DeleteMessageThreadAction())->execute($thread, $user2);
        $this->assertDatabaseMissing('message_threads', ['id' => 1]);
        $this->assertDatabaseMissing('messages', ['id' => 1]);
        $this->assertDatabaseMissing('messages', ['id' => 2]);
        $this->assertDatabaseMissing('messages', ['id' => 3]);
        $this->assertDatabaseMissing('messages', ['id' => 4]);
        $this->assertDatabaseMissing('message_thread_participants', ['id' => 1]);
        $this->assertDatabaseMissing('message_thread_participants', ['id' => 2]);

        $user2->refresh();
        $this->assertEquals(0, $user2->UnreadMessageCount);

        $this->assertEmailNotSent($user1);
        $this->assertEmailNotSent($user2);
    }

    public function testBlockedUser(): void
    {
        $now = Carbon::now()->floorSecond();
        Carbon::setTestNow($now);

        /** @var User $user1 */
        $user1 = User::factory()->create(['websitePrefs' => (1 << UserPreference::EmailOn_PrivateMessage)]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['websitePrefs' => (1 << UserPreference::EmailOn_PrivateMessage)]);

        // user1 has user2 blocked
        $relation = new UserRelation([
            'User' => $user1->User,
            'Friend' => $user2->User,
            'Friendship' => UserRelationship::Blocked,
        ]);
        $relation->save();

        // message from user2 is automatically marked as deleted by user1
        $this->captureEmails();
        $thread = (new CreateMessageThreadAction())->execute($user2, $user1, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 1,
            'last_message_id' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 1,
            'thread_id' => $thread->id,
            'author_id' => $user2->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => $now->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);

        $user1->refresh();
        $this->assertEquals(0, $user1->UnreadMessageCount);
        $this->assertEmailNotSent($user1);

        // additional message from user2 is also marked as deleted by user1
        $now2 = $now->clone()->addMinutes(5);
        Carbon::setTestNow($now2);

        $this->captureEmails();
        (new AddToMessageThreadAction())->execute($thread, $user2, 'This is a response.');

        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 2,
            'last_message_id' => 2,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 2,
            'thread_id' => $thread->id,
            'author_id' => $user2->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => $now->toDateTimeString(), /* deleted timestamp not updated */
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);

        $user1->refresh();
        $this->assertEquals(0, $user1->UnreadMessageCount);
        $this->assertEmailNotSent($user1);

        // message from user1 is delivered to user2
        Carbon::setTestNow($now);

        $this->captureEmails();
        $thread = (new CreateMessageThreadAction())->execute($user1, $user2, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 2,
            'title' => 'This is a message',
            'num_messages' => 1,
            'last_message_id' => 3,
        ]);
        $message = Message::firstWhere('id', 1);
        $this->assertDatabaseHas('messages', [
            'id' => 3,
            'thread_id' => $thread->id,
            'author_id' => $user1->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user2->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);

        $user2->refresh();
        $this->assertEquals(1, $user2->UnreadMessageCount);
        $this->assertEmailSent($user2, "New Private Message from {$user1->User}");

        // additional message from user1 is also delivered
        Carbon::setTestNow($now2);

        $this->captureEmails();
        (new AddToMessageThreadAction())->execute($thread, $user1, 'This is a response.');

        $this->assertDatabaseHas('message_threads', [
            'id' => 2,
            'title' => 'This is a message',
            'num_messages' => 2,
            'last_message_id' => 4,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 4,
            'thread_id' => $thread->id,
            'author_id' => $user1->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user2->ID,
            'num_unread' => 2,
            'deleted_at' => null,
        ]);

        $user2->refresh();
        $this->assertEquals(2, $user2->UnreadMessageCount);
        $this->assertEmailSent($user2, "New Private Message from {$user1->User}");

        // response from user2 is ignored (no unread counter or email, but not deleted)
        $now3 = $now2->clone()->addMinutes(5);
        Carbon::setTestNow($now3);

        $this->captureEmails();
        (new AddToMessageThreadAction())->execute($thread, $user2, 'This is another response.');

        $this->assertDatabaseHas('message_threads', [
            'id' => 2,
            'title' => 'This is a message',
            'num_messages' => 3,
            'last_message_id' => 5,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 5,
            'thread_id' => $thread->id,
            'author_id' => $user2->ID,
            'body' => 'This is another response.',
            'created_at' => $now3->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => $thread->id,
            'user_id' => $user2->ID,
            'num_unread' => 2,
            'deleted_at' => null,
        ]);

        $user1->refresh();
        $this->assertEquals(0, $user1->UnreadMessageCount);
        $this->assertEmailNotSent($user1);
    }

    public function testDeleteUserDeletesThread(): void
    {
        $now = Carbon::now()->floorSecond();
        Carbon::setTestNow($now);

        /** @var User $user1 */
        $user1 = User::factory()->create(['websitePrefs' => (1 << UserPreference::EmailOn_PrivateMessage)]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['websitePrefs' => (1 << UserPreference::EmailOn_PrivateMessage)]);

        // user1 sends message to user2
        $thread = (new CreateMessageThreadAction())->execute($user1, $user2, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 1,
            'last_message_id' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 1,
            'thread_id' => 1,
            'author_id' => $user1->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);

        // user2 responds
        (new ReadMessageThreadAction())->execute($thread, $user2);
        $now2 = $now->clone()->addMinutes(5);
        Carbon::setTestNow($now2);

        (new AddToMessageThreadAction())->execute($thread, $user2, 'This is a response.');
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 2,
            'last_message_id' => 2,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 2,
            'thread_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => null,
        ]);

        // user2 is deleted
        $now3 = $now2->clone()->addMinutes(5);
        Carbon::setTestNow($now3);
        (new ClearAccountDataAction())->execute($user2);
        $this->assertDatabaseHas('message_threads', [
            'id' => 1,
            'title' => 'This is a message',
            'num_messages' => 2,
            'last_message_id' => 2,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => 2,
            'thread_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user1->ID,
            'num_unread' => 1,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id' => 1,
            'user_id' => $user2->ID,
            'num_unread' => 0,
            'deleted_at' => $now3,
        ]);

        // user1 is deleted
        (new ClearAccountDataAction())->execute($user1);
        $this->assertDatabaseMissing('message_threads', ['id' => 1]);
        $this->assertDatabaseMissing('messages', ['id' => 1]);
        $this->assertDatabaseMissing('messages', ['id' => 2]);
        $this->assertDatabaseMissing('message_thread_participants', ['id' => 1]);
        $this->assertDatabaseMissing('message_thread_participants', ['id' => 2]);
    }
}
