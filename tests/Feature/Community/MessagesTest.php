<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Community\Controllers\UserMessageChainController;
use App\Community\Enums\AwardType;
use App\Community\Enums\UserRelationship;
use App\Community\Models\UserMessage;
use App\Community\Models\UserMessageChain;
use App\Community\Models\UserRelation;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class MessagesTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateMessageChain(): void
    {
        $now = Carbon::now()->floorSecond();
        Carbon::setTestNow($now);

        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        // user1 sends message to user2
        $chain = UserMessageChainController::newChain($user1, $user2, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 1,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 1,
            'sender_last_post_at' => $now->toDateTimeString(),
            'recipient_last_post_at' => null,
            'sender_deleted_at' => null,
            'recipient_deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_messages', [
            'id' => 1,
            'chain_id' => 1,
            'author_id' => $user1->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);

        // user2 responds
        $chain->recipient_num_unread = 0;
        $chain->save();
        $now2 = $now->clone()->addMinutes(5);
        Carbon::setTestNow($now2);

        UserMessageChainController::addToChain($chain, $user2, 'This is a response.');

        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 2,
            'sender_num_unread' => 1,
            'recipient_num_unread' => 0,
            'sender_last_post_at' => $now->toDateTimeString(),
            'recipient_last_post_at' => $now2->toDateTimeString(),
            'sender_deleted_at' => null,
            'recipient_deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_messages', [
            'id' => 2,
            'chain_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);

        // user2 responds again
        $now3 = $now2->clone()->addMinutes(5);
        Carbon::setTestNow($now3);

        UserMessageChainController::addToChain($chain, $user2, 'This is another response.');

        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 3,
            'sender_num_unread' => 2,
            'recipient_num_unread' => 0,
            'sender_last_post_at' => $now->toDateTimeString(),
            'recipient_last_post_at' => $now3->toDateTimeString(),
            'sender_deleted_at' => null,
            'recipient_deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_messages', [
            'id' => 3,
            'chain_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is another response.',
            'created_at' => $now3,
        ]);

        // user1 responds
        $chain->sender_num_unread = 0;
        $chain->save();
        $now4 = $now3->clone()->addMinutes(5);
        Carbon::setTestNow($now4);

        UserMessageChainController::addToChain($chain, $user1, 'This is a third response.');

        $chain->refresh();
        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 4,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 1,
            'sender_last_post_at' => $now4->toDateTimeString(),
            'recipient_last_post_at' => $now3->toDateTimeString(),
            'sender_deleted_at' => null,
            'recipient_deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_messages', [
            'id' => 4,
            'chain_id' => 1,
            'author_id' => $user1->ID,
            'body' => 'This is a third response.',
            'created_at' => $now4->toDateTimeString(),
        ]);

        // user1 deletes
        $now5 = $now4->clone()->addMinutes(5);
        Carbon::setTestNow($now5);

        UserMessageChainController::deleteChain($chain, $user1);

        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 4,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 1,
            'sender_last_post_at' => $now4->toDateTimeString(),
            'recipient_last_post_at' => $now3->toDateTimeString(),
            'sender_deleted_at' => $now5->toDateTimeString(),
            'recipient_deleted_at' => null,
        ]);

        // user2 deletes
        $now6 = $now5->clone()->addMinutes(5);
        Carbon::setTestNow($now6);

        UserMessageChainController::deleteChain($chain, $user2);

        $chain->refresh();
        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 4,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 0,
            'sender_last_post_at' => $now4->toDateTimeString(),
            'recipient_last_post_at' => $now3->toDateTimeString(),
            'sender_deleted_at' => $now5->toDateTimeString(),
            'recipient_deleted_at' => $now6->toDateTimeString(),
        ]);
    }

    public function testBlockedUser(): void
    {
        $now = Carbon::now()->floorSecond();
        Carbon::setTestNow($now);

        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        // user1 has user2 blocked
        $relation = new UserRelation([
            'User' => $user1->User,
            'Friend' => $user2->User,
            'Friendship' => UserRelationship::Blocked,
        ]);
        $relation->save();

        // message from user2 is automatically marked as deleted by user1
        $chain = UserMessageChainController::newChain($user2, $user1, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('user_message_chains', [
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user2->ID,
            'recipient_id' => $user1->ID,
            'num_messages' => 1,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 0,
            'sender_last_post_at' => $now->toDateTimeString(),
            'recipient_last_post_at' => null,
            'sender_deleted_at' => null,
            'recipient_deleted_at' => $now->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('user_messages', [
            'id' => 1,
            'chain_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);

        // additional message from user2 is also marked as deleted by user1
        $now2 = $now->clone()->addMinutes(5);
        Carbon::setTestNow($now2);

        UserMessageChainController::addToChain($chain, $user2, 'This is a response.');

        $this->assertDatabaseHas('user_message_chains',[
            'id' => 1,
            'title' => 'This is a message',
            'sender_id' => $user2->ID,
            'recipient_id' => $user1->ID,
            'num_messages' => 2,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 0,
            'sender_last_post_at' => $now2->toDateTimeString(),
            'recipient_last_post_at' => null,
            'sender_deleted_at' => null,
            'recipient_deleted_at' => $now2->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('user_messages',  [
            'id' => 2,
            'chain_id' => 1,
            'author_id' => $user2->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);

        // message from user1 is delivered to user2
        Carbon::setTestNow($now);

        $chain = UserMessageChainController::newChain($user1, $user2, 'This is a message', 'This is the message body.');
        $this->assertDatabaseHas('user_message_chains',[
            'id' => 2,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 1,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 1,
            'sender_last_post_at' => $now->toDateTimeString(),
            'recipient_last_post_at' => null,
            'sender_deleted_at' => null,
            'recipient_deleted_at' => null,
        ]);
        $message = UserMessage::firstWhere('id', 1);
        $this->assertDatabaseHas('user_messages',  [
            'id' => 3,
            'chain_id' => 2,
            'author_id' => $user1->ID,
            'body' => 'This is the message body.',
            'created_at' => $now->toDateTimeString(),
        ]);

        // additional message from user1 is also delivered
        Carbon::setTestNow($now2);

        UserMessageChainController::addToChain($chain, $user1, 'This is a response.');

        $this->assertDatabaseHas('user_message_chains',[
            'id' => 2,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 2,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 2,
            'sender_last_post_at' => $now2->toDateTimeString(),
            'recipient_last_post_at' => null,
            'sender_deleted_at' => null,
            'recipient_deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_messages',  [
            'id' => 4,
            'chain_id' => 2,
            'author_id' => $user1->ID,
            'body' => 'This is a response.',
            'created_at' => $now2->toDateTimeString(),
        ]);

        // response from user2 is automatically deleted
        $now3 = $now2->clone()->addMinutes(5);
        Carbon::setTestNow($now3);

        UserMessageChainController::addToChain($chain, $user2, 'This is another response.');

        $this->assertDatabaseHas('user_message_chains',[
            'id' => 2,
            'title' => 'This is a message',
            'sender_id' => $user1->ID,
            'recipient_id' => $user2->ID,
            'num_messages' => 3,
            'sender_num_unread' => 0,
            'recipient_num_unread' => 2,
            'sender_last_post_at' => $now2->toDateTimeString(),
            'recipient_last_post_at' => $now3->toDateTimeString(),
            'sender_deleted_at' => $now3->toDateTimeString(),
            'recipient_deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_messages',  [
            'id' => 5,
            'chain_id' => 2,
            'author_id' => $user2->ID,
            'body' => 'This is another response.',
            'created_at' => $now3->toDateTimeString(),
        ]);
    }
}
