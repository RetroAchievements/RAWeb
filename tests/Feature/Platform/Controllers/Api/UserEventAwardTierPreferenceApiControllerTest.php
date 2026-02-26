<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers\Api;

use App\Community\Enums\AwardType;
use App\Models\Event;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEventAwardTierPreferenceApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUserGets401(): void
    {
        // Arrange
        $event = $this->createEvent();

        // Act
        $response = $this->putJson(route('api.user.event-award-tier-preference.update'), [
            'eventId' => $event->id,
            'tierIndex' => 0,
        ]);

        // Assert
        $response->assertStatus(401);
    }

    public function testAuthenticatedUserCanSetPreferredTier(): void
    {
        // Arrange
        $event = $this->createEvent();

        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 0]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 1]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 2]);

        /** @var User $user */
        $user = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'award_type' => AwardType::Event,
            'award_key' => $event->id,
            'award_tier' => 2, // !! earned up to tier 2
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.user.event-award-tier-preference.update'), [
                'eventId' => $event->id,
                'tierIndex' => 1, // !! setting tier 1
            ]);

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_awards', [
            'user_id' => $user->id,
            'award_type' => AwardType::Event->value,
            'award_key' => $event->id,
            'display_award_tier' => 1, // !!
        ]);
    }

    public function testUserCannotSetTierHigherThanEarned(): void
    {
        // Arrange
        $event = $this->createEvent();

        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 0]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 1]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 2]);

        /** @var User $user */
        $user = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'award_type' => AwardType::Event,
            'award_key' => $event->id,
            'award_tier' => 1, // !!
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.user.event-award-tier-preference.update'), [
                'eventId' => $event->id,
                'tierIndex' => 2, // !! higher than 1, should be invalid
            ]);

        // Assert
        $response->assertStatus(422);
    }

    public function testReturns422WhenTierDoesNotExistForEvent(): void
    {
        // Arrange
        $event = $this->createEvent();

        // ... only tiers 0 and 1 exist for this event ...
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 0]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 1]);

        /** @var User $user */
        $user = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'award_type' => AwardType::Event,
            'award_key' => $event->id,
            'award_tier' => 1,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.user.event-award-tier-preference.update'), [
                'eventId' => $event->id,
                'tierIndex' => 99, // !!
            ]);

        // Assert
        $response->assertStatus(422);
    }

    public function testReturns404WhenUserHasNoAwardForEvent(): void
    {
        // Arrange
        $event = $this->createEvent();

        /** @var User $user */
        $user = User::factory()->create();

        // ... no EventAward::factory()->create(...) here ...

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.user.event-award-tier-preference.update'), [
                'eventId' => $event->id,
                'tierIndex' => 0,
            ]);

        // Assert
        $response->assertStatus(404);
    }

    private function createEvent(): Event
    {
        /** @var System $system */
        $system = System::factory()->create(['id' => System::Events]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        return Event::factory()->create(['legacy_game_id' => $game->id]);
    }
}
