<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Services;

use App\Actions\ClearAccountDataAction;
use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Models\Comment;
use App\Models\Subscription;
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

    private function subscribeToGameWall(User $user, int $gameId): void
    {
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, $gameId, true);
    }

    private function unsubscribeToGameWall(User $user, int $gameId): void
    {
        $this->updateSubscription($user, SubscriptionSubjectType::GameWall, $gameId, false);
    }

    public function testExplicitlySubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->subscribeToGameWall($user, 3);

        $service = new SubscriptionService();
        
        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscriptions = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscriptions->count());
        $this->assertEquals($user->id, $subscriptions->first()->user_id);
    }

    public function testExplicitlyUnsubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->unsubscribeToGameWall($user, 3);

        $service = new SubscriptionService();
        
        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscriptions = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscriptions->count());
    }

    public function testImplicitlyNotSubscribed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $service = new SubscriptionService();
        
        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscriptions = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscriptions->count());
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

        $subscriptions = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscriptions->count());
        $this->assertEquals($user->id, $subscriptions->first()->user_id);
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
        $this->subscribeToGameWall($user, 3);

        $service = new SubscriptionService();
        
        $this->assertTrue($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscriptions = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(1, $subscriptions->count());
        $this->assertEquals($user->id, $subscriptions->first()->user_id);
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
        $this->unsubscribeToGameWall($user, 3);

        $service = new SubscriptionService();
        
        $this->assertFalse($service->isSubscribed($user, SubscriptionSubjectType::GameWall, 3));

        $subscriptions = $service->getSubscribers(SubscriptionSubjectType::GameWall, 3);
        $this->assertEquals(0, $subscriptions->count());
    }
}
