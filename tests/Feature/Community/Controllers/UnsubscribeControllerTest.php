<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\ForumTopic;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsubscribeControllerTest extends TestCase
{
    use RefreshDatabase;

    private UnsubscribeService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UnsubscribeService();
        $this->user = User::factory()->create();
    }

    public function testGetRequestWithValidSignatureShowsPage(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertStatus(200);

        // ... verify the unsubscribe was processed ...
        $this->assertDatabaseHas('Subscriptions', [
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => false, // !! unsubscribed
        ]);
    }

    public function testGetRequestWithInvalidSignatureReturns403(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // ... break the URL ...
        $url = str_replace('signature=', 'signature=invalid', $url); // !!

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertStatus(403);

        // ... verify the unsubscribe was NOT processed ...
        $this->assertDatabaseMissing('Subscriptions', [
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
        ]);
    }

    public function testPostRequestWithValidSignatureReturns200(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $response = $this->post($url, [
            'List-Unsubscribe' => 'One-Click', // !! RFC 8058 body parameter
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('', false); // the response body is empty - that's good

        // ... verify the unsubscribe was processed ...
        $this->assertDatabaseHas('Subscriptions', [
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => false,
        ]);
    }

    public function testPostRequestWithInvalidSignatureReturns403(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // ... break the URL ...
        $url = str_replace('signature=', 'signature=invalid', $url);

        // Act
        $response = $this->post($url, [
            'List-Unsubscribe' => 'One-Click',
        ]);

        // Assert
        $response->assertStatus(403);

        // ... verify the unsubscribe was NOT processed ...
        $this->assertDatabaseMissing('Subscriptions', [
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
        ]);
    }

    public function testPostRequestWithoutProperBodyReturns400(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $response = $this->post($url, [
            'List-Unsubscribe' => 'Invalid', // !! wrong value
        ]);

        // Assert
        $response->assertStatus(400);

        // ... verify the unsubscribe was NOT processed ...
        $this->assertDatabaseMissing('Subscriptions', [
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
        ]);
    }

    public function testPostRequestDoesNotGenerateUndoToken(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        // ... create an explicit subscription ...
        Subscription::create([
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => true, // !! subscribed
        ]);

        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $response = $this->post($url, [
            'List-Unsubscribe' => 'One-Click',
        ]);

        // Assert
        $response->assertStatus(200);

        // ... verify the unsubscribe was processed ...
        $subscription = Subscription::where('user_id', $this->user->id)
            ->where('subject_type', SubscriptionSubjectType::ForumTopic)
            ->where('subject_id', $forumTopic->id)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertFalse($subscription->state); // !! unsubscribed
    }

    public function testPostRequestForCategoryUnsubscribeWorks(): void
    {
        // Arrange
        $initialPrefs = (1 << UserPreference::EmailOn_ForumReply);
        $user = User::factory()->create(['websitePrefs' => $initialPrefs]);

        $url = $this->service->generateCategoryUrl(
            $user,
            UserPreference::EmailOn_ForumReply
        );

        // Act
        $response = $this->post($url, [
            'List-Unsubscribe' => 'One-Click',
        ]);

        // Assert
        $response->assertStatus(200);

        // ... verify the preference bit was turned off ...
        $user->refresh();
        $isSet = ($user->websitePrefs & (1 << UserPreference::EmailOn_ForumReply)) !== 0;
        $this->assertFalse($isSet);
    }
}
