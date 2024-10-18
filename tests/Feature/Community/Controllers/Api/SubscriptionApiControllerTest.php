<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers\Api;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItAllowsTheUserToSubscribe(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('api.subscription.store', ['GameWall', 1]));

        // Assert
        $response
            ->assertStatus(201)
            ->assertJson(['data' => [
                'subjectType' => 'GameWall',
                'subjectId' => 1,
                'state' => true,
            ]]);
    }

    public function testItAllowsTheUserToUnsubscribe(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        Subscription::create([
            'subject_type' => 'GameWall',
            'subject_id' => 1,
            'user_id' => $user->id,
            'state' => true,
        ]);

        // Act
        $response = $this->delete(route('api.subscription.destroy', ['GameWall', 1]));

        // Assert
        $response->assertStatus(204);

        $activeSubscriptionsCount = Subscription::whereState(true)->count();
        $this->assertEquals(0, $activeSubscriptionsCount);
    }
}
