<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class EventAchievementsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

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

    private function createEventAchievementForEvent(
        Event $event,
        array $eventAchievementAttributes = [],
        array $achievementAttributes = [],
    ): EventAchievement {
        $sourceAchievementAttributes = array_intersect_key(
            $achievementAttributes,
            array_flip(['title', 'description', 'image_name']),
        );
        $mirror = Achievement::factory()->promoted()->create([
            ...$achievementAttributes,
            'game_id' => $event->legacy_game_id,
        ]);

        return EventAchievement::factory()->create([
            ...$eventAchievementAttributes,
            'achievement_id' => $mirror->id,
            'source_achievement_id' => Achievement::factory()->create($sourceAchievementAttributes)->id,
        ]);
    }

    public function testItListsEventAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => now()->subMonth(),
            'image_asset_path' => 'Images/Events/aotw.png',
        ]);
        $eventAchievement = $this->createEventAchievementForEvent($event, [], [
            'title' => 'Challenge Accepted',
            'description' => 'Complete the weekly challenge.',
            'points' => 10,
            'image_name' => '12345',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $eventAchievement->id],
        ]);
        $attributes = collect($response->json('data'))
            ->firstWhere('id', (string) $eventAchievement->id)['attributes'];
        $this->assertEquals('Challenge Accepted', $attributes['achievementTitle']);
        $this->assertEquals('Complete the weekly challenge.', $attributes['achievementDescription']);
        $this->assertEquals(10, $attributes['achievementPoints']);
        $this->assertStringEndsWith('/Badge/12345.png', $attributes['achievementBadgeUrl']);
        $this->assertStringEndsWith('/Badge/12345_lock.png', $attributes['achievementBadgeLockedUrl']);
        $this->assertEquals($game->title, $attributes['eventTitle']);
        $this->assertStringEndsWith('/Images/Events/aotw.png', $attributes['eventBadgeUrl']);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function testItRequiresAuthenticationForTheIndex(): void
    {
        // Arrange
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $this->createEventAchievementForEvent($event);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->get('/api/v2/event-achievements');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItDoesNotListFutureEventAchievementsForRegularUsers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $activeGame = Game::factory()->create(['players_total' => 1000]);
        $activeEvent = Event::factory()->create(['legacy_game_id' => $activeGame->id, 'active_from' => now()->subMonth()]);
        $activeEventAchievement = $this->createEventAchievementForEvent($activeEvent);

        $futureGame = Game::factory()->create(['players_total' => 1000]);
        $futureEvent = Event::factory()->create(['legacy_game_id' => $futureGame->id, 'active_from' => now()->addMonth()]);
        $this->createEventAchievementForEvent($futureEvent);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $activeEventAchievement->id],
        ]);
    }

    public function testItDoesNotListAchievementsForGamesWithFutureEventsForRegularUsers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $futureEvent = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->addMonth()]);
        $this->createEventAchievementForEvent($futureEvent);

        // Act
        $indexResponse = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements');
        $filteredResponse = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements?filter[eventId]={$futureEvent->id}");

        // Assert
        $indexResponse->assertSuccessful();
        $this->assertSame([], $indexResponse->json('data'));
        $filteredResponse->assertSuccessful();
        $this->assertSame([], $filteredResponse->json('data'));
    }

    public function testItListsFutureEventAchievementsForEventManagers(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $user->assignRole(Role::EVENT_MANAGER);

        $futureGame = Game::factory()->create(['players_total' => 1000]);
        $futureEvent = Event::factory()->create(['legacy_game_id' => $futureGame->id, 'active_from' => now()->addMonth()]);
        $futureEventAchievement = $this->createEventAchievementForEvent($futureEvent);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $futureEventAchievement->id],
        ]);
    }

    public function testItIncludesEventAchievementRelationshipsOnTheIndex(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $eventAchievement = $this->createEventAchievementForEvent($event);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?include=event,eventAchievement,sourceAchievement');

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));
        $this->assertTrue($included->contains(fn (array $resource): bool => $resource['type'] === 'events' && $resource['id'] === (string) $event->id));
        $this->assertTrue($included->contains(fn (array $resource): bool => $resource['type'] === 'achievements' && $resource['id'] === (string) $eventAchievement->achievement_id));
        $this->assertTrue($included->contains(fn (array $resource): bool => $resource['type'] === 'achievements' && $resource['id'] === (string) $eventAchievement->source_achievement_id));
    }

    public function testIndexFilterActiveTrueReturnsOnlyInWindowAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $activeEventAchievement = $this->createEventAchievementForEvent($event, [
            'active_from' => now()->subDay(),
            'active_until' => now()->addWeek(),
        ]);
        $this->createEventAchievementForEvent($event, [
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subDay(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[active]=true');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $activeEventAchievement->id],
        ]);
    }

    public function testItFiltersTheIndexByEventId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $firstGame = Game::factory()->create(['players_total' => 1000]);
        $firstEvent = Event::factory()->create(['legacy_game_id' => $firstGame->id, 'active_from' => now()->subMonth()]);
        $firstEventAchievement = $this->createEventAchievementForEvent($firstEvent);

        $secondGame = Game::factory()->create(['players_total' => 1000]);
        $secondEvent = Event::factory()->create(['legacy_game_id' => $secondGame->id, 'active_from' => now()->subMonth()]);
        $this->createEventAchievementForEvent($secondEvent);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements?filter[eventId]={$firstEvent->id}");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $firstEventAchievement->id],
        ]);
    }

    public function testItFiltersTheIndexByMultipleEventIds(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $firstGame = Game::factory()->create(['players_total' => 1000]);
        $firstEvent = Event::factory()->create(['legacy_game_id' => $firstGame->id, 'active_from' => now()->subMonth()]);
        $firstEventAchievement = $this->createEventAchievementForEvent($firstEvent);

        $secondGame = Game::factory()->create(['players_total' => 1000]);
        $secondEvent = Event::factory()->create(['legacy_game_id' => $secondGame->id, 'active_from' => now()->subMonth()]);
        $secondEventAchievement = $this->createEventAchievementForEvent($secondEvent);

        $thirdGame = Game::factory()->create(['players_total' => 1000]);
        $thirdEvent = Event::factory()->create(['legacy_game_id' => $thirdGame->id, 'active_from' => now()->subMonth()]);
        $this->createEventAchievementForEvent($thirdEvent);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements?filter[eventId]={$firstEvent->id},{$secondEvent->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertEqualsCanonicalizing(
            [(string) $firstEventAchievement->id, (string) $secondEventAchievement->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function testFilterEventIdRejectsNonNumericValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[eventId]=not-a-number');

        // Assert
        $response->assertStatus(400);
        $this->assertEquals('invalid_filter', $response->json('errors.0.code'));
    }

    public function testFilterEventIdWithNoAchievementsReturnsAnEmptyCollection(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements?filter[eventId]={$event->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertSame([], $response->json('data'));
    }

    public function testFilterEventIdDoesNotRevealFutureEvents(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->addMonth()]);
        $this->createEventAchievementForEvent($event);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/event-achievements?filter[eventId]={$event->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertSame([], $response->json('data'));
    }

    public function testRelationshipFilterEventIdReturnsTheParentEventsAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $eventAchievement = $this->createEventAchievementForEvent($event);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/events/{$event->id}/event-achievements?filter[eventId]={$event->id}");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $eventAchievement->id],
        ]);
    }

    public function testFilterEvergreenFalseExcludesAchievementsWithNoActiveUntil(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $timeBounded = $this->createEventAchievementForEvent($event, ['active_until' => now()->addWeek()]);
        $this->createEventAchievementForEvent($event, ['active_until' => null]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[evergreen]=false');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $timeBounded->id],
        ]);
    }

    public function testFilterEvergreenTrueReturnsOnlyAchievementsWithNoActiveUntil(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $this->createEventAchievementForEvent($event, ['active_until' => now()->addWeek()]);
        $evergreen = $this->createEventAchievementForEvent($event, ['active_until' => null]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[evergreen]=true');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $evergreen->id],
        ]);
    }

    public function testFilterEvergreenRejectsNonBooleanValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[evergreen]=notabool');

        // Assert
        $response->assertStatus(400);
        $this->assertEquals('invalid_filter', $response->json('errors.0.code'));
    }

    public function testFilterEvergreenUsesPerAchievementActiveUntil(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $evergreen = $this->createEventAchievementForEvent($event, ['active_until' => null]);
        $this->createEventAchievementForEvent($event, ['active_until' => now()->addWeek()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[evergreen]=true');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $evergreen->id],
        ]);
    }

    public function testFilterActiveTrueComposesWithEvergreenFalse(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
        $activeTimeBounded = $this->createEventAchievementForEvent($event, [
            'active_from' => now()->subDay(),
            'active_until' => now()->addWeek(),
        ]);
        $this->createEventAchievementForEvent($event, [
            'active_from' => now()->subDay(),
            'active_until' => null,
        ]);
        $this->createEventAchievementForEvent($event, [
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subDay(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('event-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/event-achievements?filter[active]=true&filter[evergreen]=false');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'event-achievements', 'id' => (string) $activeTimeBounded->id],
        ]);
    }

    public function testItReturnsCorrectAttributesAndRelationshipIdentifiers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'title' => 'Mirror Title',
            'description' => 'Mirror description.',
            'points' => 25,
            'image_name' => '67890',
        ]);
        $source = Achievement::factory()->create([
            'title' => 'Source Title',
            'description' => 'Source description.',
            'points' => 5,
            'image_name' => '09876',
        ]);
        $event = Event::factory()->create([
            'legacy_game_id' => $game->id,
            'active_from' => now()->subMonth(),
            'image_asset_path' => 'Images/Events/show.png',
        ]);
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
        $this->assertEquals((string) $mirror->id, $response->json('data.relationships.eventAchievement.data.id'));
        $this->assertEquals($source->title, $attributes['achievementTitle']);
        $this->assertEquals($source->description, $attributes['achievementDescription']);
        $this->assertEquals($mirror->points, $attributes['achievementPoints']);
        $this->assertEquals($source->badge_url, $attributes['achievementBadgeUrl']);
        $this->assertEquals($source->badge_locked_url, $attributes['achievementBadgeLockedUrl']);
        $this->assertEquals($game->title, $attributes['eventTitle']);
        $this->assertEquals($event->badge_url, $attributes['eventBadgeUrl']);
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

    public function testItPreventsAccessWhenAnyMatchingEventIsInTheFuture(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
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

    public function testItAllowsEventManagersToAccessFutureEventAchievements(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $user->assignRole(Role::EVENT_MANAGER);

        $game = Game::factory()->create(['players_total' => 1000]);
        $mirror = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        Event::factory()->create(['legacy_game_id' => $game->id, 'active_from' => now()->subMonth()]);
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
        $response->assertSuccessful();
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
