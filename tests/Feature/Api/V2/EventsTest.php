<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class EventsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'events';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/events';
    }

    protected function createResource(): Model
    {
        if (!System::find(System::Events)) {
            System::factory()->create(['id' => System::Events]);
        }

        $game = Game::factory()->create(['system_id' => System::Events]);

        return Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::now()->subMonth(),
            'active_until' => Carbon::now()->addYear(),
        ]);
    }

    public function testItListsEvents(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create([
            'title' => 'Achievement of the Week',
            'system_id' => System::Events,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/events');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'events', 'id' => (string) $event->id],
        ]);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create([
            'title' => 'Achievement of the Week',
            'system_id' => System::Events,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::parse('2025-01-01'),
            'active_until' => Carbon::parse('2025-12-31'),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Achievement of the Week', $attributes['title']);
        $this->assertArrayHasKey('sortTitle', $attributes);
        $this->assertArrayHasKey('badgeUrl', $attributes);
        $this->assertArrayHasKey('state', $attributes);
        $this->assertArrayHasKey('playersTotal', $attributes);
        $this->assertArrayHasKey('achievementsPublished', $attributes);
        $this->assertArrayHasKey('activeFrom', $attributes);
        $this->assertArrayHasKey('activeThrough', $attributes);
    }

    public function testItCanIncludeAwardsRelationship(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create(['system_id' => System::Events]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);
        $award = EventAward::factory()->create([
            'event_id' => $event->id,
            'label' => 'Gold',
            'points_required' => 50,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}?include=awards");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'events',
            'id' => (string) $event->id,
        ]);

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('event-awards', $included[0]['type']);
        $this->assertEquals((string) $award->id, $included[0]['id']);
        $this->assertEquals('Gold', $included[0]['attributes']['label']);
        $this->assertEquals(50, $included[0]['attributes']['pointsRequired']);
    }

    public function testItReturnsCorrectStateForActiveEvent(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create(['system_id' => System::Events]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::now()->subMonth(),
            'active_until' => Carbon::now()->addYear(),
        ]);

        $achievement = Achievement::factory()->create(['game_id' => $game->id]);
        EventAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'active_from' => Carbon::now()->subMonth(), // !!
            'active_until' => Carbon::now()->addYear(), // !!
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('active', $response->json('data.attributes.state'));
    }

    public function testItReturnsCorrectStateForConcludedEvent(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create(['system_id' => System::Events]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::now()->subYear(),
            'active_until' => Carbon::now()->subMonth(),
        ]);

        $achievement = Achievement::factory()->create(['game_id' => $game->id]);
        EventAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'active_from' => Carbon::now()->subYear(), // !!
            'active_until' => Carbon::now()->subMonth(), // !!
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('concluded', $response->json('data.attributes.state'));
    }

    public function testItReturnsPlayerAndAchievementCounts(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create([
            'system_id' => System::Events,
            'players_total' => 250,
            'achievements_published' => 15,
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals(250, $attributes['playersTotal']);
        $this->assertEquals(15, $attributes['achievementsPublished']);
    }

    public function testItSortsByTitle(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);

        $game1 = Game::factory()->create([
            'title' => 'Zelda Challenge',
            'system_id' => System::Events,
        ]);
        Event::factory()->create([
            'legacy_game_id' => $game1->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);

        $game2 = Game::factory()->create([
            'title' => 'Achievement of the Week',
            'system_id' => System::Events,
        ]);
        Event::factory()->create([
            'legacy_game_id' => $game2->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/events?sort=title');

        // Assert
        $response->assertSuccessful();
        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertEquals('Achievement of the Week', $titles[0]);
        $this->assertEquals('Zelda Challenge', $titles[1]);
    }

    public function testItSortsBySortTitle(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);

        $game1 = Game::factory()->create([
            'title' => 'The Big Event',
            'sort_title' => 'big event',
            'system_id' => System::Events,
        ]);
        Event::factory()->create([
            'legacy_game_id' => $game1->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);

        $game2 = Game::factory()->create([
            'title' => 'Achievement of the Week',
            'sort_title' => 'achievement of the week',
            'system_id' => System::Events,
        ]);
        Event::factory()->create([
            'legacy_game_id' => $game2->id,
            'active_from' => Carbon::now()->subMonth(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/events?sort=sortTitle');

        // Assert
        $response->assertSuccessful();
        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertEquals('Achievement of the Week', $titles[0]);
        $this->assertEquals('The Big Event', $titles[1]);
    }

    public function testItSortsByActiveFrom(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);

        $game1 = Game::factory()->create(['system_id' => System::Events]);
        Event::factory()->create([
            'legacy_game_id' => $game1->id,
            'active_from' => Carbon::parse('2024-06-01'),
        ]);

        $game2 = Game::factory()->create(['system_id' => System::Events]);
        Event::factory()->create([
            'legacy_game_id' => $game2->id,
            'active_from' => Carbon::parse('2024-01-01'),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/events?sort=activeFrom');

        // Assert
        $response->assertSuccessful();
        $dates = collect($response->json('data'))->pluck('attributes.activeFrom')->toArray();
        $this->assertLessThan(0, strcmp($dates[0], $dates[1]));
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);

        for ($i = 0; $i < 60; $i++) {
            $game = Game::factory()->create(['system_id' => System::Events]);
            Event::factory()->create([
                'legacy_game_id' => $game->id,
                'active_from' => Carbon::now()->subMonth(),
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('events')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/events');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
    }
}
