<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Services;

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        ], [
            'state' => $state,
        ]);
    }

    public function testExplicitlySubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['id' => 3]);

        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, true);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);

        $this->assertEquals(1, $service->getSubscriptionCount($user, [SubscriptionSubjectType::GameWall]));
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameWall]);
        $this->assertEquals(1, count($subscriptions));
        $subscription = $subscriptions->get(0);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(3, $subscription->subject_id);
        $this->assertEquals($game->title, $subscription->title);
        $this->assertTrue($subscription->exists);
    }

    public function testExplicitlyUnsubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['id' => 3]);

        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, false);

        $service = new SubscriptionService();

        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscribers->count());

        $this->assertEquals(0, $service->getSubscriptionCount($user, [SubscriptionSubjectType::GameWall]));
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameWall]);
        $this->assertEquals(0, count($subscriptions));
    }

    public function testImplicitlyNotSubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['id' => 3]);

        $service = new SubscriptionService();

        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscribers->count());

        $this->assertEquals(0, $service->getSubscriptionCount($user, [SubscriptionSubjectType::GameWall]));
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameWall]);
        $this->assertEquals(0, count($subscriptions));
    }

    public function testImplicitlySubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['id' => 3]);

        Comment::create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => 3,
            'user_id' => $user->id,
            'body' => 'Test',
        ]);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);

        $this->assertEquals(1, $service->getSubscriptionCount($user, [SubscriptionSubjectType::GameWall]));
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameWall]);
        $this->assertEquals(1, count($subscriptions));
        $subscription = $subscriptions->get(0);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(3, $subscription->subject_id);
        $this->assertEquals($game->title, $subscription->title);
        $this->assertFalse($subscription->exists); // implicit subscription has no backing row
    }

    public function testImplicitlySubscribedWithExplicitSubscription(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['id' => 3]);

        Comment::create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => 3,
            'user_id' => $user->id,
            'body' => 'Test',
        ]);
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, true);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscribers->count());
        $this->assertEquals($user->id, $subscribers->first()->id);

        $this->assertEquals(1, $service->getSubscriptionCount($user, [SubscriptionSubjectType::GameWall]));
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameWall]);
        $this->assertEquals(1, count($subscriptions));
        $subscription = $subscriptions->get(0);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(3, $subscription->subject_id);
        $this->assertEquals($game->title, $subscription->title);
        $this->assertTrue($subscription->exists);
    }

    public function testImplicitlySubscribedWithExplicitUnsubscription(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create(['id' => 3]);

        Comment::create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => 3,
            'user_id' => $user->id,
            'body' => 'Test',
        ]);
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 3, false);

        $service = new SubscriptionService();

        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscribers->count());

        $this->assertEquals(0, $service->getSubscriptionCount($user, [SubscriptionSubjectType::GameWall]));
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameWall]);
        $this->assertEquals(0, count($subscriptions));
    }

    public function testAchievementWallSubcribers(): void
    {
        $this->seed(RolesTableSeeder::class);

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
        $user7->assignRole(Role::DEVELOPER);
        /** @var User $user8 */
        $user8 = User::factory()->create();

        /** @var Achievement $achievement */
        $achievement = $this->seedAchievement();
        $achievement->user_id = $user7->id;
        $achievement->save();

        // user1 implicitly subscribed to achievement via comment
        Comment::create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $user1->id,
            'body' => 'Test',
            'created_at' => Carbon::now()->subDays(8),
        ]);

        // user2 explicitly subscribed to achievement
        $this->updateSubscription($user2, SubscriptionSubjectType::Achievement, $achievement->id, true);

        // user3 implicitly subscribed to achievement via comment, but explicitly unsubscribed
        Comment::create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $user3->id,
            'body' => 'Test',
            'created_at' => Carbon::now()->subDays(4),
        ]);
        $this->updateSubscription($user3, SubscriptionSubjectType::Achievement, $achievement->id, false);

        // user4 implicitly subscribed via explicit subscription to GameAchievements
        $this->updateSubscription($user4, SubscriptionSubjectType::GameAchievements, $achievement->game->id, true);

        // user5 implicitly subscribed to achievement via comment, but explicitly unsubscribed from GameAchievements - implicit achievement subscription wins
        Comment::create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $user5->id,
            'body' => 'Test',
            'created_at' => Carbon::now()->subDays(2),
        ]);
        $this->updateSubscription($user5, SubscriptionSubjectType::GameAchievements, $achievement->game->id, false);

        // user6 implicitly subscribed to achievement via subscription to GameAchievements, but explicitly unsubscribed from achievement - explicit achievement subscription wins
        $this->updateSubscription($user6, SubscriptionSubjectType::GameAchievements, $achievement->game->id, true);
        $this->updateSubscription($user6, SubscriptionSubjectType::Achievement, $achievement->id, false);

        // user7 implicitly subscribed to achievement as author

        // user8 has explicit unsubscription to GameAchievements
        $this->updateSubscription($user8, SubscriptionSubjectType::GameAchievements, $achievement->game->id, false);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user1, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertTrue($service->isSubscribed($user2, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertFalse($service->isSubscribed($user3, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertTrue($service->isSubscribed($user4, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertTrue($service->isSubscribed($user5, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertFalse($service->isSubscribed($user6, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertTrue($service->isSubscribed($user7, SubscriptionSubjectType::Achievement, $achievement->id));
        $this->assertFalse($service->isSubscribed($user8, SubscriptionSubjectType::Achievement, $achievement->id));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::Achievement, $achievement->id);
        $subscribedUserIds = $subscribers->pluck('id')->toArray();
        $this->assertEqualsCanonicalizing([1, 2, 4, 5, 7], $subscribedUserIds);

        // user2 is explicitly subscribed. user 7 is the author. user 5 has a recent comments.
        // user1 has an old comment. user4 is only implicitly subscribed via GameAchievements.
        $segmentedSubscribers = $service->getSegmentedSubscriberIds(SubscriptionSubjectType::Achievement, $achievement->id, $achievement->user_id);
        $this->assertEqualsCanonicalizing([2], $segmentedSubscribers['explicitlySubscribed']);
        $this->assertEqualsCanonicalizing([5, 7], $segmentedSubscribers['implicitlySubscribedNotifyNow']);
        $this->assertEqualsCanonicalizing([1, 4], $segmentedSubscribers['implicitlySubscribedNotifyLater']);
    }

    public function testTicketSubscribers(): void
    {
        $this->seed(RolesTableSeeder::class);

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
        /** @var User $user8 */
        $user8 = User::factory()->create();
        $user8->assignRole(Role::DEVELOPER);
        /** @var User $user9 */
        $user9 = User::factory()->create();

        /** @var Achievement $achievement */
        $achievement = $this->seedAchievement();
        $achievement->user_id = $user8->id;
        $achievement->save();
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['ticketable_id' => $achievement->id, 'reporter_id' => $user7->id]);

        // user1 implicitly subscribed to achievement via comment
        Comment::create([
            'commentable_type' => CommentableType::AchievementTicket,
            'commentable_id' => $achievement->id,
            'user_id' => $user1->id,
            'body' => 'Test',
        ]);

        // user2 explicitly subscribed to ticket
        $this->updateSubscription($user2, SubscriptionSubjectType::AchievementTicket, $ticket->id, true);

        // user3 implicitly subscribed to ticket via comment, but explicitly unsubscribed
        Comment::create([
            'commentable_type' => CommentableType::AchievementTicket,
            'commentable_id' => $ticket->id,
            'user_id' => $user3->id,
            'body' => 'Test',
        ]);
        $this->updateSubscription($user3, SubscriptionSubjectType::AchievementTicket, $ticket->id, false);

        // user4 implicitly subscribed to ticket via explicit subscription to GameTickets
        $this->updateSubscription($user4, SubscriptionSubjectType::GameTickets, $achievement->game->id, true);

        // user5 implicitly subscribed to ticket via comment, but explicitly unsubscribed from GameTickets - implicit ticket subscription wins
        Comment::create([
            'commentable_type' => CommentableType::AchievementTicket,
            'commentable_id' => $ticket->id,
            'user_id' => $user5->id,
            'body' => 'Test',
        ]);
        $this->updateSubscription($user5, SubscriptionSubjectType::GameTickets, $achievement->game->id, false);

        // user6 implicitly subscribed to ticket via subscription to GameTickets, but explicitly unsubscribed from ticket - explicit ticket subscription wins
        $this->updateSubscription($user6, SubscriptionSubjectType::GameTickets, $achievement->game->id, true);
        $this->updateSubscription($user6, SubscriptionSubjectType::AchievementTicket, $ticket->id, false);

        // user7 implicitly subscribed to ticket via being the reporter

        // user8 implicitly subscribed to ticket via being the achievement author

        // user9 has explicit unsubscription to GameTickets
        $this->updateSubscription($user9, SubscriptionSubjectType::GameTickets, $achievement->game->id, false);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user1, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertTrue($service->isSubscribed($user2, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertFalse($service->isSubscribed($user3, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertTrue($service->isSubscribed($user4, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertTrue($service->isSubscribed($user5, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertFalse($service->isSubscribed($user6, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertTrue($service->isSubscribed($user7, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertTrue($service->isSubscribed($user8, SubscriptionSubjectType::AchievementTicket, $ticket->id));
        $this->assertFalse($service->isSubscribed($user9, SubscriptionSubjectType::AchievementTicket, $ticket->id));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::AchievementTicket, $ticket->id);
        $subscribedUserIds = $subscribers->pluck('id')->toArray();
        $this->assertEqualsCanonicalizing([1, 2, 4, 5, 7, 8], $subscribedUserIds);
    }

    public function testForumTopicSubscribers(): void
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

        // user3 started the topic
        /** @var ForumTopic $topic */
        $topic = ForumTopic::factory()->create(['author_id' => $user3->id]);
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user3->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // user4 posted over a week ago
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user4->id,
            'created_at' => Carbon::now()->subDays(9),
        ]);

        // user2 posted over a week ago
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user2->id,
            'created_at' => Carbon::now()->subDays(8),
        ]);

        // user6 posted under a week ago
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user6->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // user2 posted again under a week ago
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user2->id,
            'created_at' => Carbon::now()->subDays(4),
        ]);

        // user5 posted again under a week ago, but explicitly unsubscribed
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user5->id,
            'created_at' => Carbon::now()->subDays(3),
        ]);
        $this->updateSubscription($user5, SubscriptionSubjectType::ForumTopic, $topic->id, false);

        // user7 is explicitly subscribed, but has never posted
        $this->updateSubscription($user7, SubscriptionSubjectType::ForumTopic, $topic->id, true);

        // user6 just posted now
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $user6->id,
            'created_at' => Carbon::now(),
        ]);

        $service = new SubscriptionService();

        $this->assertFalse($service->isSubscribed($user1, SubscriptionSubjectType::ForumTopic, $topic->id));
        $this->assertTrue($service->isSubscribed($user2, SubscriptionSubjectType::ForumTopic, $topic->id));
        $this->assertTrue($service->isSubscribed($user3, SubscriptionSubjectType::ForumTopic, $topic->id));
        $this->assertTrue($service->isSubscribed($user4, SubscriptionSubjectType::ForumTopic, $topic->id));
        $this->assertFalse($service->isSubscribed($user5, SubscriptionSubjectType::ForumTopic, $topic->id));
        $this->assertTrue($service->isSubscribed($user6, SubscriptionSubjectType::ForumTopic, $topic->id));
        $this->assertTrue($service->isSubscribed($user7, SubscriptionSubjectType::ForumTopic, $topic->id));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::ForumTopic, $topic->id);
        $subscribedUserIds = $subscribers->pluck('id')->toArray();
        $this->assertEqualsCanonicalizing([2, 3, 4, 6, 7], $subscribedUserIds);

        $segmentedSubscribers = $service->getSegmentedSubscriberIds(SubscriptionSubjectType::ForumTopic, $topic->id, $topic->author_id);
        $this->assertEqualsCanonicalizing([7], $segmentedSubscribers['explicitlySubscribed']);
        $this->assertEqualsCanonicalizing([2, 3, 6], $segmentedSubscribers['implicitlySubscribedNotifyNow']);
        $this->assertEqualsCanonicalizing([4], $segmentedSubscribers['implicitlySubscribedNotifyLater']);

    }

    public function testUserWallSubscribers(): void
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

        // user2 implicitly subscribed to wall via comment
        Comment::create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $user1->id,
            'user_id' => $user2->id,
            'body' => 'Test',
        ]);

        // user4 explicitly subscribed to wall
        $this->updateSubscription($user4, SubscriptionSubjectType::UserWall, $user1->id, true);

        // user5 implicitly subscribed to achievement via comment, but explicitly unsubscribed
        Comment::create([
            'commentable_type' => CommentableType::User,
            'commentable_id' => $user1->id,
            'user_id' => $user5->id,
            'body' => 'Test',
        ]);
        $this->updateSubscription($user5, SubscriptionSubjectType::UserWall, $user1->id, false);

        $service = new SubscriptionService();

        $this->assertTrue($service->isSubscribed($user1, SubscriptionSubjectType::UserWall, $user1->id));
        $this->assertTrue($service->isSubscribed($user2, SubscriptionSubjectType::UserWall, $user1->id));
        $this->assertFalse($service->isSubscribed($user3, SubscriptionSubjectType::UserWall, $user1->id));
        $this->assertTrue($service->isSubscribed($user4, SubscriptionSubjectType::UserWall, $user1->id));
        $this->assertFalse($service->isSubscribed($user5, SubscriptionSubjectType::UserWall, $user1->id));

        $subscribers = $service->getSubscribers(SubscriptionSubjectType::UserWall, $user1->id);
        $subscribedUserIds = $subscribers->pluck('id')->toArray();
        $this->assertEqualsCanonicalizing([1, 2, 4], $subscribedUserIds);
    }

    public function testGetSubscriptions(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Game $game1 */
        $game1 = Game::factory()->create(['id' => 1, 'title' => 'One']);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['id' => 2, 'title' => 'Two']);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['id' => 3, 'title' => 'Three']);
        /** @var Game $game4 */
        $game4 = Game::factory()->create(['id' => 4, 'title' => 'Four']);

        // explicitly subscribed to GameWall 2 and 4
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 2, true);
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, 4, true);

        // implicitly subscribed to GameWall 3
        Comment::create([
            'commentable_type' => CommentableType::Game,
            'commentable_id' => 3,
            'user_id' => $user->id,
            'body' => 'Test',
        ]);

        // explicitly subscribed to GameAchievements 3
        $this->updateSubscription($user, SubscriptionSubjectType::GameAchievements, 3, true);

        // explicitly subscribed to GameTickets 2
        $this->updateSubscription($user, SubscriptionSubjectType::GameTickets, 2, true);

        $service = new SubscriptionService();

        $allSubjectTypes = [
            SubscriptionSubjectType::GameWall,
            SubscriptionSubjectType::GameAchievements,
            SubscriptionSubjectType::GameTickets,
        ];

        $this->assertEquals(5, $service->getSubscriptionCount($user, $allSubjectTypes));
        $subscriptions = $service->getSubscriptions($user, $allSubjectTypes);
        $this->assertEquals(5, count($subscriptions));

        $subscription = $subscriptions->get(0);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(4, $subscription->subject_id);
        $this->assertEquals('Four', $subscription->title);
        $this->assertTrue($subscription->exists);

        $subscription = $subscriptions->get(1);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(3, $subscription->subject_id);
        $this->assertEquals('Three', $subscription->title);
        $this->assertFalse($subscription->exists);

        $subscription = $subscriptions->get(2);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(2, $subscription->subject_id);
        $this->assertEquals('Two', $subscription->title);
        $this->assertTrue($subscription->exists);

        $subscription = $subscriptions->get(3);
        $this->assertEquals(SubscriptionSubjectType::GameAchievements, $subscription->subject_type);
        $this->assertEquals(3, $subscription->subject_id);
        $this->assertEquals('Three', $subscription->title);
        $this->assertTrue($subscription->exists);

        $subscription = $subscriptions->get(4);
        $this->assertEquals(SubscriptionSubjectType::GameTickets, $subscription->subject_type);
        $this->assertEquals(2, $subscription->subject_id);
        $this->assertEquals('Two', $subscription->title);
        $this->assertTrue($subscription->exists);

        // offset 2, limit 2
        $subscriptions = $service->getSubscriptions($user, $allSubjectTypes, 2, 2);
        $this->assertEquals(2, count($subscriptions));

        $subscription = $subscriptions->get(0);
        $this->assertEquals(SubscriptionSubjectType::GameWall, $subscription->subject_type);
        $this->assertEquals(2, $subscription->subject_id);
        $this->assertEquals('Two', $subscription->title);
        $this->assertTrue($subscription->exists);

        $subscription = $subscriptions->get(1);
        $this->assertEquals(SubscriptionSubjectType::GameAchievements, $subscription->subject_type);
        $this->assertEquals(3, $subscription->subject_id);
        $this->assertEquals('Three', $subscription->title);
        $this->assertTrue($subscription->exists);

        // only game tickets
        $subscriptions = $service->getSubscriptions($user, [SubscriptionSubjectType::GameTickets]);
        $this->assertEquals(1, count($subscriptions));

        $subscription = $subscriptions->get(0);
        $this->assertEquals(SubscriptionSubjectType::GameTickets, $subscription->subject_type);
        $this->assertEquals(2, $subscription->subject_id);
        $this->assertEquals('Two', $subscription->title);
        $this->assertTrue($subscription->exists);
    }
}
