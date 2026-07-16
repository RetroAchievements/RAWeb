<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EventAchievementsTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'event-achievements';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/event-achievements';
    }

    protected function createResource(): Model
    {
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $source = Achievement::factory()->create();
        Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);

        return EventAchievement::factory()->create([
            'achievement_id' => $mirror->id,
            'source_achievement_id' => $source->id,
        ]);
    }

    public function testItReturnsCorrectAttributesAndRelationshipIdentifiers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $source = Achievement::factory()->create();
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $eventAchievement = EventAchievement::factory()->create([
            'achievement_id' => $mirror->id,
            'source_achievement_id' => $source->id,
            'decorator' => 'winner',
            'active_from' => '2026-03-01',
            'active_until' => '2026-03-08',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements/{$eventAchievement->id}");

        // Assert
        $response->assertSuccessful();

        $attributes = $response->json('data.attributes');
        $this->assertEquals('winner', $attributes['decorator']);
        $this->assertStringStartsWith('2026-03-01', $attributes['activeFrom']);
        $this->assertStringStartsWith('2026-03-08', $attributes['activeUntil']);

        $this->assertEquals('events', $response->json('data.relationships.event.data.type'));
        $this->assertEquals((string) $event->id, $response->json('data.relationships.event.data.id'));

        $this->assertEquals('achievements', $response->json('data.relationships.sourceAchievement.data.type'));
        $this->assertEquals((string) $source->id, $response->json('data.relationships.sourceAchievement.data.id'));

        $this->assertEquals('achievements', $response->json('data.relationships.eventAchievement.data.type'));
        $this->assertEquals((string) $mirror->id, $response->json('data.relationships.eventAchievement.data.id'));

        $this->assertStringEndsWith("/achievement/{$mirror->id}", $response->json('data.links.webUrl'));
        $this->assertStringEndsWith("/api/v2/event-achievements/{$eventAchievement->id}", $response->json('data.links.self'));
    }

    public function testItPreventsAccessToEventAchievementsForFutureEvents(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->addMonth()]);
        $eventAchievement = EventAchievement::factory()->create([
            'achievement_id' => $mirror->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements/{$eventAchievement->id}");

        // Assert
        $response->assertForbidden();
    }

    public function testItReturnsEventAchievementsForAnEvent(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $eventAchievement = EventAchievement::factory()->create([
            'achievement_id' => $mirror->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
        ]);

        // ... an unrelated event achievement that must not leak into this event ...
        $otherGame = Game::factory()->create();
        $otherMirror = Achievement::factory()->promoted()->create(['game_id' => $otherGame->id]);
        Event::factory()->create(['legacy_game_id' => $otherGame->id, 'active_from' => now()->subMonth()]);
        EventAchievement::factory()->create([
            'achievement_id' => $otherMirror->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $eventAchievement->id], $ids);
    }

    public function testItExcludesUnpublishedDraftAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);

        $published = EventAchievement::factory()->create([
            'achievement_id' => Achievement::factory()->promoted()->create(['game_id' => $game->id])->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subDay(),
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => Achievement::factory()->create(['game_id' => $game->id, 'is_promoted' => false])->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subDay(),
        ]);

        // Act
        $base = $this->jsonApi('v2')->expects('event-achievements')->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements");
        $inactive = $this->jsonApi('v2')->expects('event-achievements')->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements?filter[active]=false");

        // Assert
        $this->assertEquals([(string) $published->id], collect($base->json('data'))->pluck('id')->all());
        $this->assertEquals([(string) $published->id], collect($inactive->json('data'))->pluck('id')->all());
    }

    public function testFilterActiveTrueReturnsOnlyInWindowAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);

        $activeEventAchievement = EventAchievement::factory()->create([
            'achievement_id' => Achievement::factory()->promoted()->create(['game_id' => $game->id])->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subDay(),
            'active_until' => now()->addWeek(),
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => Achievement::factory()->promoted()->create(['game_id' => $game->id])->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subDay(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements?filter[active]=true");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $activeEventAchievement->id], $ids);
    }

    public function testFilterActiveFalseReturnsOnlyOutOfWindowAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);

        EventAchievement::factory()->create([
            'achievement_id' => Achievement::factory()->promoted()->create(['game_id' => $game->id])->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subDay(),
            'active_until' => now()->addWeek(),
        ]);

        $expiredEventAchievement = EventAchievement::factory()->create([
            'achievement_id' => Achievement::factory()->promoted()->create(['game_id' => $game->id])->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subDay(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements?filter[active]=false");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $expiredEventAchievement->id], $ids);
    }

    public function testFilterActiveRejectsNonBooleanValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements?filter[active]=notabool");

        // Assert
        $response->assertStatus(400);
    }

    public function testItHydratesDistinctSourceAndEventAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $source = Achievement::factory()->create();
        Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $eventAchievement = EventAchievement::factory()->create([
            'achievement_id' => $mirror->id,
            'source_achievement_id' => $source->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements/{$eventAchievement->id}?include=sourceAchievement,eventAchievement");

        // Assert
        $response->assertSuccessful();

        $includedAchievementIds = collect($response->json('included'))
            ->where('type', 'achievements')
            ->pluck('id')
            ->all();

        $this->assertContains((string) $source->id, $includedAchievementIds);
        $this->assertContains((string) $mirror->id, $includedAchievementIds);
        $this->assertNotEquals($source->id, $mirror->id);
    }

    public function testItHydratesTheEventThroughRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $eventAchievement = EventAchievement::factory()->create([
            'achievement_id' => $mirror->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements/{$eventAchievement->id}?include=event");

        // Assert
        $response->assertSuccessful();

        $includedEvent = collect($response->json('included'))->firstWhere('type', 'events');
        $this->assertNotNull($includedEvent);
        $this->assertEquals((string) $event->id, $includedEvent['id']);
    }
}
