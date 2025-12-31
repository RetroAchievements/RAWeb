<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GamesTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'games';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/games';
    }

    protected function createResource(): Model
    {
        $system = System::factory()->create();

        return Game::factory()->create(['system_id' => $system->id]);
    }

    public function testItListsGames(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Super Mario Bros.',
            'system_id' => $system->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'games', 'id' => (string) $game->id],
        ]);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        Game::factory()->count(100)->create(['system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));

        $this->assertGreaterThan(50, $response->json('meta.page.total'));
        $this->assertArrayHasKey('next', $response->json('links'));
    }

    public function testItFiltersBySystemId(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system1 = System::factory()->create();
        $system2 = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system1->id]);
        $game2 = Game::factory()->create(['system_id' => $system2->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games?filter[systemId]={$system1->id}");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $game1->id, $ids);
        $this->assertNotContains((string) $game2->id, $ids);
    }

    public function testItExcludesHubGames(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $gameSystem = System::factory()->create();
        System::factory()->create(['id' => System::Hubs]);

        $normalGame = Game::factory()->create(['system_id' => $gameSystem->id]);
        $hubGame = Game::factory()->create(['system_id' => System::Hubs]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalGame->id, $ids);
        $this->assertNotContains((string) $hubGame->id, $ids);
    }

    public function testItExcludesEventGames(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $gameSystem = System::factory()->create();
        System::factory()->create(['id' => System::Events]);

        $normalGame = Game::factory()->create(['system_id' => $gameSystem->id]);
        $eventGame = Game::factory()->create(['system_id' => System::Events]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalGame->id, $ids);
        $this->assertNotContains((string) $eventGame->id, $ids);
    }

    public function testItExcludesSubsetGames(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();

        $normalGame = Game::factory()->create(['system_id' => $system->id]);
        $subsetGame = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Pokemon Red [Subset - Professor Oak Challenge]',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalGame->id, $ids);
        $this->assertNotContains((string) $subsetGame->id, $ids);
    }

    public function testItSortsByTitle(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        Game::factory()->create(['title' => 'Zelda', 'system_id' => $system->id]);
        Game::factory()->create(['title' => 'Asteroids', 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games?sort=title');

        // Assert
        $response->assertSuccessful();

        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertLessThan(0, strcmp($titles[0], $titles[1]));
    }

    public function testItSortsByPointsTotalDescending(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        Game::factory()->create(['points_total' => 100, 'system_id' => $system->id]);
        Game::factory()->create(['points_total' => 500, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games?sort=-pointsTotal');

        // Assert
        $response->assertSuccessful();
        $points = collect($response->json('data'))->pluck('attributes.pointsTotal')->toArray();
        $this->assertGreaterThan($points[1], $points[0]);
    }

    public function testItCanIncludeSystemRelationship(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create(['name' => 'Nintendo 64']);
        $game = Game::factory()->create(['system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}?include=system");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'games',
            'id' => (string) $game->id,
        ]);

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('systems', $included[0]['type']);
        $this->assertEquals((string) $system->id, $included[0]['id']);
        $this->assertEquals('Nintendo 64', $included[0]['attributes']['name']);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Test Game',
            'system_id' => $system->id,
            'achievements_published' => 50,
            'points_total' => 500,
            'points_weighted' => 1000,
            'players_total' => 100,
            'players_hardcore' => 75,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Test Game', $attributes['title']);
        $this->assertEquals(50, $attributes['achievementsPublished']);
        $this->assertEquals(500, $attributes['pointsTotal']);
        $this->assertEquals(1000, $attributes['pointsWeighted']);
        $this->assertEquals(100, $attributes['playersTotal']);
        $this->assertEquals(75, $attributes['playersHardcore']);
        $this->assertArrayHasKey('badgeUrl', $attributes);
        $this->assertArrayHasKey('imageBoxArtUrl', $attributes);
        $this->assertArrayHasKey('imageTitleUrl', $attributes);
        $this->assertArrayHasKey('imageIngameUrl', $attributes);
    }

    public function testItIncludesForumTopicLinkWhenPresent(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        $forumTopic = ForumTopic::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'forum_topic_id' => $forumTopic->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}");

        // Assert
        $response->assertSuccessful();
        $links = $response->json('data.links');

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('forumTopic', $links);
        $this->assertStringContainsString("/forums/topic/{$forumTopic->id}", $links['forumTopic']);
    }

    public function testItOmitsForumTopicLinkWhenNotPresent(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'forum_topic_id' => null,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}");

        // Assert
        $response->assertSuccessful();
        $links = $response->json('data.links');

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayNotHasKey('forumTopic', $links);
    }
}
