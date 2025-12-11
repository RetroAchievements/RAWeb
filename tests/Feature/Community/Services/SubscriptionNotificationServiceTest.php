<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Services;

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionNotificationService;
use App\Enums\UserPreference;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGetEmailTargets(): void
    {
        $userPreference = UserPreference::EmailOn_ForumReply;
        $websitePrefs = 1 << $userPreference;

        /** @var User $user1 */
        $user1 = User::factory()->create(['websitePrefs' => $websitePrefs]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['websitePrefs' => 0xFFFF & ~$websitePrefs]);
        /** @var User $user3 */
        $user3 = User::factory()->create(['websitePrefs' => $websitePrefs, 'EmailAddress' => '']);
        /** @var User $user4 */
        $user4 = User::factory()->create(['websitePrefs' => 0]);
        /** @var User $user5 */
        $user5 = User::factory()->create(['websitePrefs' => $websitePrefs]);

        $service = new SubscriptionNotificationService();
        $emailTargets = $service->getEmailTargets([$user1->id, $user2->id, $user3->id, $user4->id, $user5->id], $userPreference);
        $emailTargetUserIds = $emailTargets->pluck('ID')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id, $user5->id], $emailTargetUserIds);
    }

    public function testQueueNotifications(): void
    {
        $userPreference = UserPreference::EmailOn_ForumReply;
        $websitePrefs = 1 << $userPreference;

        /** @var User $user1 */
        $user1 = User::factory()->create(['websitePrefs' => $websitePrefs]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['websitePrefs' => 0xFFFF & ~$websitePrefs]);
        /** @var User $user3 */
        $user3 = User::factory()->create(['websitePrefs' => $websitePrefs, 'EmailAddress' => '']);
        /** @var User $user4 */
        $user4 = User::factory()->create(['websitePrefs' => 0]);
        /** @var User $user5 */
        $user5 = User::factory()->create(['websitePrefs' => $websitePrefs]);
        /** @var User $user6 */
        $user6 = User::factory()->create(['websitePrefs' => $websitePrefs]);

        $service = new SubscriptionNotificationService();

        // empty list does nothing
        $service->queueNotifications([],
            SubscriptionSubjectType::ForumTopic, 7, 33, $userPreference);
        $this->assertEquals(0, UserDelayedSubscription::count());

        // user2 has email disabled, user3 has no email address, do nothing
        $service->queueNotifications([$user2->id, $user3->id],
            SubscriptionSubjectType::ForumTopic, 7, 33, $userPreference);
        $this->assertEquals(0, UserDelayedSubscription::count());

        // only users 1 and 5 have valid email addresses and the appropriate preferences
        $service->queueNotifications([$user1->id, $user2->id, $user3->id, $user4->id, $user5->id],
            SubscriptionSubjectType::ForumTopic, 7, 33, $userPreference);
        $queuedUserIds = UserDelayedSubscription::pluck('user_id')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id, $user5->id], $queuedUserIds);

        foreach (UserDelayedSubscription::all() as $subscription) {
            $this->assertEquals(SubscriptionSubjectType::ForumTopic, $subscription->subject_type);
            $this->assertEquals(7, $subscription->subject_id);
            $this->assertEquals(33, $subscription->first_update_id);
        }

        // existing notifications should not be updated. new notification should be added for user6.
        $service->queueNotifications([$user2->id, $user3->id, $user4->id, $user5->id, $user6->id],
            SubscriptionSubjectType::ForumTopic, 7, 34, $userPreference);
        $queuedUserIds = UserDelayedSubscription::pluck('user_id')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id, $user5->id, $user6->id], $queuedUserIds);

        foreach (UserDelayedSubscription::all() as $subscription) {
            $this->assertEquals(SubscriptionSubjectType::ForumTopic, $subscription->subject_type);
            $this->assertEquals(7, $subscription->subject_id);

            if ($subscription->user_id === $user6->id) {
                $this->assertEquals(34, $subscription->first_update_id);
            } else {
            $this->assertEquals(33, $subscription->first_update_id);
            }
        }
    }

    public function testResetNotification(): void
    {
        $userPreference = UserPreference::EmailOn_ForumReply;
        $websitePrefs = 1 << $userPreference;

        /** @var User $user1 */
        $user1 = User::factory()->create(['websitePrefs' => $websitePrefs]);
        /** @var User $user2 */
        $user2 = User::factory()->create(['websitePrefs' => $websitePrefs]);

        $service = new SubscriptionNotificationService();

        // queue notification for user1
        $service->queueNotifications([$user1->id], SubscriptionSubjectType::ForumTopic, 7, 33, $userPreference);
        $queuedUserIds = UserDelayedSubscription::pluck('user_id')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id], $queuedUserIds);

        // no subscription for user2 - do nothing
        $service->resetNotification($user2->id, SubscriptionSubjectType::ForumTopic, 7);
        $queuedUserIds = UserDelayedSubscription::pluck('user_id')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id], $queuedUserIds);

        // queue notification for user2
        $service->queueNotifications([$user2->id], SubscriptionSubjectType::ForumTopic, 7, 34, $userPreference);
        $queuedUserIds = UserDelayedSubscription::pluck('user_id')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id], $queuedUserIds);

        // reset subscription for user2 - does not affect user1
        $service->resetNotification($user2->id, SubscriptionSubjectType::ForumTopic, 7);
        $queuedUserIds = UserDelayedSubscription::pluck('user_id')->toArray();
        $this->assertEqualsCanonicalizing([$user1->id], $queuedUserIds);
        $this->assertEquals(33, UserDelayedSubscription::where('user_id', $user1->id)->first()->first_update_id);
    }
}
