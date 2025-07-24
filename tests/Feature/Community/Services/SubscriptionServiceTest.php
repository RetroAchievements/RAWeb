<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Services;

use App\Actions\ClearAccountDataAction;
use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Models\Comment;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function updateSubscription(User $user, SubscriptionSubjectType $subjectType, int $subjectId, bool $state): void
    {
        Subscription::updateOrCreate([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'user_id' => $user->id,
        ],[
            'state' => $state,
        ]);
    }

    public function testExplicitlySubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, true);

        $service = new SubscriptionService();
        
        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);
    }

    public function testExplicitlyUnsubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, false);

        $service = new SubscriptionService();
        
        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscribers->count());
    }

    public function testImplicitlyNotSubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $service = new SubscriptionService();
        
        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscribers->count());
    }

    public function testImplicitlySubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Comment::create([
            'ArticleType' => ArticleType::Game,
            'ArticleID' => 3,
            'user_id' => $user->id,
            'Payload' => 'Test',
        ]);

        $service = new SubscriptionService();
        
        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);
    }
        
    public function testImplicitlySubscribedWithExplicitSubscription(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Comment::create([
            'ArticleType' => ArticleType::Game,
            'ArticleID' => 3,
            'user_id' => $user->id,
            'Payload' => 'Test',
        ]);
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, true);

        $service = new SubscriptionService();
        
        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);
    }

    public function testImplicitlySubscribedWithExplicitUnsubscription(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Comment::create([
            'ArticleType' => ArticleType::Game,
            'ArticleID' => 3,
            'user_id' => $user->id,
            'Payload' => 'Test',
        ]);
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, false);

        $service = new SubscriptionService();
        
        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscribers->count());
    }

    public function testAchievementSubcriptionIncludesGameAchievementsSubscribers(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();
        /** @var User $user5 */
        $user5 = User::factory()->create();
        /** @var User $user6 */
        $user6 = User::factory()->create();

        /** @var Achievement $achievement */
        $achievement = $this->seedAchievement();

        // user1 implicitly subscribed to achievement via comment
        Comment::create([
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => $achievement->ID,
            'user_id' => $user1->id,
            'Payload' => 'Test',
        ]);

        // user2 explicitly subscribed to achievement
        $this->updateSubscription($user2, SubscriptionSubjectType::Achievement, $achievement->ID, true);

        // user3 implicitly subscribed to achievement via comment, but explicitly unsubscribed
        Comment::create([
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => $achievement->ID,
            'user_id' => $user3->id,
            'Payload' => 'Test',
        ]);
        $this->updateSubscription($user3, SubscriptionSubjectType::Achievement, $achievement->ID, false);

        // user4 implicitly subscribed via explicit subscription to GameAchievements
        $this->updateSubscription($user4, SubscriptionSubjectType::GameAchievements, $achievement->game->ID, true);

        // user5 implicitly subscribed to achievement via comment, but explicitly unsubscribed from GameAchievements - implicit achievement subscription wins
        Comment::create([
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => $achievement->ID,
            'user_id' => $user5->id,
            'Payload' => 'Test',
        ]);
        $this->updateSubscription($user5, SubscriptionSubjectType::GameAchievements, $achievement->game->ID, false);

        // user6 implicitly subscribed to achievement via subscription to GameAchievements, but explicitly unsubscribed from achievement - explicit achievement subscription wins
        $this->updateSubscription($user6, SubscriptionSubjectType::GameAchievements, $achievement->game->ID, true);
        $this->updateSubscription($user6, SubscriptionSubjectType::Achievement, $achievement->ID, false);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user1, SubscriptionSubjectType::Achievement, $achievement->ID));
        $this->assertTrue($service->isSubscribed($user2, SubscriptionSubjectType::Achievement, $achievement->ID));
        $this->assertFalse($service->isSubscribed($user3, SubscriptionSubjectType::Achievement, $achievement->ID));
        $this->assertTrue($service->isSubscribed($user4, SubscriptionSubjectType::Achievement, $achievement->ID));
        $this->assertTrue($service->isSubscribed($user5, SubscriptionSubjectType::Achievement, $achievement->ID));
        $this->assertFalse($service->isSubscribed($user6, SubscriptionSubjectType::Achievement, $achievement->ID));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::Achievement, $achievement->ID);
        $subscribedUserIds = $subscribers->pluck('id')->toArray();
        $this->assertEqualsCanonicalizing([1, 2, 4, 5], $subscribedUserIds);
    }

    public function testTicketSubcriptionIncludesGameTicketsSubscribers(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();
        /** @var User $user5 */
        $user5 = User::factory()->create();
        /** @var User $user6 */
        $user6 = User::factory()->create();
        /** @var User $user7 */
        $user7 = User::factory()->create();

        /** @var Achievement $achievement */
        $achievement = $this->seedAchievement();
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['AchievementID' => $achievement->ID, 'reporter_id' => $user7->id]);

        // user1 implicitly subscribed to achievement via comment
        Comment::create([
            'ArticleType' => ArticleType::AchievementTicket,
            'ArticleID' => $achievement->ID,
            'user_id' => $user1->id,
            'Payload' => 'Test',
        ]);

        // user2 explicitly subscribed to ticket
        $this->updateSubscription($user2, SubscriptionSubjectType::AchievementTicket, $ticket->ID, true);

        // user3 implicitly subscribed to ticket via comment, but explicitly unsubscribed
        Comment::create([
            'ArticleType' => ArticleType::AchievementTicket,
            'ArticleID' => $ticket->ID,
            'user_id' => $user3->id,
            'Payload' => 'Test',
        ]);
        $this->updateSubscription($user3, SubscriptionSubjectType::AchievementTicket, $ticket->ID, false);

        // user4 implicitly subscribed to ticket via explicit subscription to GameTickets
        $this->updateSubscription($user4, SubscriptionSubjectType::GameTickets, $achievement->game->ID, true);

        // user5 implicitly subscribed to ticket via comment, but explicitly unsubscribed from GameTickets - implicit ticket subscription wins
        Comment::create([
            'ArticleType' => ArticleType::AchievementTicket,
            'ArticleID' => $ticket->ID,
            'user_id' => $user5->id,
            'Payload' => 'Test',
        ]);
        $this->updateSubscription($user5, SubscriptionSubjectType::GameTickets, $achievement->game->ID, false);

        // user6 implicitly subscribed to ticket via subscription to GameTickets, but explicitly unsubscribed from ticket - explicit ticket subscription wins
        $this->updateSubscription($user6, SubscriptionSubjectType::GameTickets, $achievement->game->ID, true);
        $this->updateSubscription($user6, SubscriptionSubjectType::AchievementTicket, $ticket->ID, false);

        // user7 implicitly subscribed to ticket via being the reporter

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user1, SubscriptionSubjectType::AchievementTicket, $ticket->ID));
        $this->assertTrue($service->isSubscribed($user2, SubscriptionSubjectType::AchievementTicket, $ticket->ID));
        $this->assertFalse($service->isSubscribed($user3, SubscriptionSubjectType::AchievementTicket, $ticket->ID));
        $this->assertTrue($service->isSubscribed($user4, SubscriptionSubjectType::AchievementTicket, $ticket->ID));
        $this->assertTrue($service->isSubscribed($user5, SubscriptionSubjectType::AchievementTicket, $ticket->ID));
        $this->assertFalse($service->isSubscribed($user6, SubscriptionSubjectType::AchievementTicket, $ticket->ID));
        $this->assertTrue($service->isSubscribed($user7, SubscriptionSubjectType::AchievementTicket, $ticket->ID));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::AchievementTicket, $ticket->ID);
        $subscribedUserIds = $subscribers->pluck('id')->toArray();
        $this->assertEqualsCanonicalizing([1, 2, 4, 5, 7], $subscribedUserIds);
    }

    public function testImplicitlySubscribedForumTopic(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var ForumTopic $topic */
        $topic = ForumTopic::factory()->create();
        ForumTopicComment::factory()->create(['forum_topic_id' => $topic->id]);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::ForumTopic, $topic->id));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::ForumTopic, $topic->id);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);
    }
}
