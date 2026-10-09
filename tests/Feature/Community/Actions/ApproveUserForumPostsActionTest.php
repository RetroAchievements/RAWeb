<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\ApproveUserForumPostsAction;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Notifications\Community\CommunityActivityNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now()->startOfSecond());

    $this->user = User::factory()->create();
});

class ApproveUserForumPostsActionTestHelpers
{
    public static function createNotificationUser(): User
    {
        return User::factory()->create([
            'last_activity_at' => Carbon::now(),
            'preferences_bitfield' => 1 << UserPreference::EmailOn_ForumReply,
        ]);
    }

    public static function createUnauthorizedComment(ForumTopic $topic, User $user): ForumTopicComment
    {
        return ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user->id,
            'is_authorized' => false,
            'authorized_at' => null,
        ]);
    }

    public static function getDelayedSubscription(User $user, ForumTopic $topic): ?UserDelayedSubscription
    {
        return UserDelayedSubscription::query()
            ->where('user_id', $user->id)
            ->where('subject_type', SubscriptionSubjectType::ForumTopic)
            ->where('subject_id', $topic->id)
            ->first();
    }
}

describe('user', function () {
    test('gets marked as verified', function () {
        $action = new ApproveUserForumPostsAction();

        $this->assertNull($this->user->forum_verified_at);
        $this->assertNull($this->user->ManuallyVerified);

        $action->execute($this->user);

        $this->assertEquals(Carbon::now(), $this->user->forum_verified_at);
        $this->assertEquals(1, $this->user->ManuallyVerified);
    });
});

describe('posts', function () {
    test('get marked as authorized', function () {
        $forumTopic1 = ForumTopic::factory()->create();
        $forumTopicComment1 = ApproveUserForumPostsActionTestHelpers::createUnauthorizedComment($forumTopic1, $this->user);

        $forumTopic2 = ForumTopic::factory()->create();
        $forumTopicComment2 = ApproveUserForumPostsActionTestHelpers::createUnauthorizedComment($forumTopic2, $this->user);

        $action = new ApproveUserForumPostsAction();
        $action->execute($this->user);

        $forumTopicComment1->refresh();
        $this->assertTrue($forumTopicComment1->is_authorized);
        $this->assertEquals(Carbon::now(), $forumTopicComment1->authorized_at);

        $forumTopicComment2->refresh();
        $this->assertTrue($forumTopicComment2->is_authorized);
        $this->assertEquals(Carbon::now(), $forumTopicComment2->authorized_at);
    });

    test('result in email notifications', function () {
        $forumTopic1 = ForumTopic::factory()->create();
        $forumTopicComment1 = ApproveUserForumPostsActionTestHelpers::createUnauthorizedComment($forumTopic1, $this->user);

        // user2 explicitly subscribed to forumTopic1
        $user2 = ApproveUserForumPostsActionTestHelpers::createNotificationUser();
        Subscription::create([
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic1->id,
            'user_id' => $user2->id,
            'state' => true,
        ]);

        $forumTopic2 = ForumTopic::factory()->create();

        // $user3 implicitly subscribed to forumTopic2 by posting
        $user3 = ApproveUserForumPostsActionTestHelpers::createNotificationUser();
        ForumTopicComment::factory()->create(['forum_topic_id' => $forumTopic2->id, 'author_id' => $user3->id]);

        $forumTopicComment2 = ApproveUserForumPostsActionTestHelpers::createUnauthorizedComment($forumTopic2, $this->user);

        // $user4 implicitly subscribed to forumTopic2 by posting
        // NOTE: post is after the post being approved. email is still sent!
        $user4 = ApproveUserForumPostsActionTestHelpers::createNotificationUser();
        ForumTopicComment::factory()->create(['forum_topic_id' => $forumTopic2->id, 'author_id' => $user4->id]);

        Notification::fake();

        $action = new ApproveUserForumPostsAction();
        $action->execute($this->user);

        Notification::assertSentTo([$user2], CommunityActivityNotification::class);
        Notification::assertSentTo([$user3], CommunityActivityNotification::class);
        Notification::assertSentTo([$user4], CommunityActivityNotification::class);
    });

    test('result in delayed notifications', function () {
        // delayed notifications only go to implicit subscribers who posted more than a week ago.
        $now = Carbon::now();
        Carbon::setTestNow($now->clone()->subDays(10));

        $forumTopic1 = ForumTopic::factory()->create();
        $forumTopic2 = ForumTopic::factory()->create();

        // user2 implicitly subscribed to forumTopic1 by posting
        $user2 = ApproveUserForumPostsActionTestHelpers::createNotificationUser();
        ForumTopicComment::factory()->create(['forum_topic_id' => $forumTopic1->id, 'author_id' => $user2->id]);

        $forumTopicComment1 = ApproveUserForumPostsActionTestHelpers::createUnauthorizedComment($forumTopic1, $this->user);

        // $user3 implicitly subscribed to forumTopic2 by posting
        $user3 = ApproveUserForumPostsActionTestHelpers::createNotificationUser();
        ForumTopicComment::factory()->create(['forum_topic_id' => $forumTopic2->id, 'author_id' => $user3->id]);

        $forumTopicComment2 = ApproveUserForumPostsActionTestHelpers::createUnauthorizedComment($forumTopic2, $this->user);

        // $user4 implicitly subscribed to forumTopic2 by posting
        // NOTE: post is after the post being approved. delayed notification will not be sent!
        $user4 = ApproveUserForumPostsActionTestHelpers::createNotificationUser();
        ForumTopicComment::factory()->create(['forum_topic_id' => $forumTopic2->id, 'author_id' => $user4->id]);

        Carbon::setTestNow($now);

        $action = new ApproveUserForumPostsAction();
        $action->execute($this->user);

        // user2 should be notified about the new post as it's newer than anything they've already seen.
        $user2Subscription = ApproveUserForumPostsActionTestHelpers::getDelayedSubscription($user2, $forumTopic1);
        $this->assertNotNull($user2Subscription);
        $this->assertEquals($forumTopicComment1->id, $user2Subscription->first_update_id);

        // notifications should not have been queued for users 3 and 4 as they would have already
        // been notified about the newer post.
        $this->assertEquals(0, UserDelayedSubscription::where('user_id', $user3->id)->count());
        $this->assertEquals(0, UserDelayedSubscription::where('user_id', $user4->id)->count());
    });
});
