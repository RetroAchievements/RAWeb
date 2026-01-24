<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class HubsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'hubs';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/hubs';
    }

    protected function createResource(): Model
    {
        return GameSet::factory()->create(['type' => GameSetType::Hub]);
    }

    public function testItListsHubs(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create([
            'title' => '[Series - Mario]',
            'type' => GameSetType::Hub,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'hubs', 'id' => (string) $hub->id],
        ]);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        GameSet::factory()->count(100)->create(['type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));

        $this->assertGreaterThan(50, $response->json('meta.page.total'));
        $this->assertArrayHasKey('next', $response->json('links'));
    }

    public function testItExcludesSimilarGamesType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $similarGames = GameSet::factory()->create(['type' => GameSetType::SimilarGames]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $hub->id, $ids);
        $this->assertNotContains((string) $similarGames->id, $ids);
    }

    public function testItFiltersByParentId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $parentHub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $childHub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $otherHub = GameSet::factory()->create(['type' => GameSetType::Hub]);

        $parentHub->children()->attach($childHub->id);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs?filter[parentId]={$parentHub->id}");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $childHub->id, $ids);
        $this->assertNotContains((string) $otherHub->id, $ids);
        $this->assertNotContains((string) $parentHub->id, $ids);
    }

    public function testItFiltersByTitle(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $marioHub = GameSet::factory()->create([
            'title' => '[Series - Mario]',
            'type' => GameSetType::Hub,
        ]);
        $zeldaHub = GameSet::factory()->create([
            'title' => '[Series - Zelda]',
            'type' => GameSetType::Hub,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs?filter[title]=Mario');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $marioHub->id, $ids);
        $this->assertNotContains((string) $zeldaHub->id, $ids);
    }

    public function testItSortsByTitleAscending(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        GameSet::factory()->create(['title' => 'Zelda Hub', 'type' => GameSetType::Hub]);
        GameSet::factory()->create(['title' => 'Asteroids Hub', 'type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs?sort=title');

        // Assert
        $response->assertSuccessful();
        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertLessThan(0, strcmp($titles[0], $titles[1]));
    }

    public function testItSortsByTitleDescending(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        GameSet::factory()->create(['title' => 'Asteroids Hub', 'type' => GameSetType::Hub]);
        GameSet::factory()->create(['title' => 'Zelda Hub', 'type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs?sort=-title');

        // Assert
        $response->assertSuccessful();
        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertGreaterThan(0, strcmp($titles[0], $titles[1]));
    }

    public function testItReturnsGamesViaPaginatedRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Super Mario Bros.',
        ]);
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $hub->games()->attach($game->id);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}/games");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'games', 'id' => (string) $game->id],
        ]);

        $this->assertEquals('Super Mario Bros.', $response->json('data.0.attributes.title'));
    }

    public function testItPaginatesGamesRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);

        // ... create 60 games and attach them to the hub ...
        $games = Game::factory()->count(60)->create(['system_id' => $system->id]);
        $hub->games()->attach($games->pluck('id'));

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}/games");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertArrayHasKey('next', $response->json('links'));
    }

    public function testItReturnsParentsViaPaginatedRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $parentHub = GameSet::factory()->create([
            'title' => '[Central Hub]',
            'type' => GameSetType::Hub,
        ]);
        $childHub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $parentHub->children()->attach($childHub->id);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$childHub->id}/parents");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'hubs', 'id' => (string) $parentHub->id],
        ]);

        $this->assertEquals('[Central Hub]', $response->json('data.0.attributes.title'));
    }

    public function testItReturnsChildrenViaPaginatedRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $parentHub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $childHub = GameSet::factory()->create([
            'title' => '[Series - Mario]',
            'type' => GameSetType::Hub,
        ]);
        $parentHub->children()->attach($childHub->id);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$parentHub->id}/children");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'hubs', 'id' => (string) $childHub->id],
        ]);

        $this->assertEquals('[Series - Mario]', $response->json('data.0.attributes.title'));
    }

    public function testItPaginatesChildrenRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $parentHub = GameSet::factory()->create(['type' => GameSetType::Hub]);

        // ... create 60 child hubs and attach them ...
        $childHubs = GameSet::factory()->count(60)->create(['type' => GameSetType::Hub]);
        $parentHub->children()->attach($childHubs->pluck('id'));

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$parentHub->id}/children");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertArrayHasKey('next', $response->json('links'));
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create([
            'title' => '[Series - Mario]',
            'has_mature_content' => false,
            'type' => GameSetType::Hub,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('[Series - Mario]', $attributes['title']);
        $this->assertArrayHasKey('sortTitle', $attributes);
        $this->assertArrayHasKey('badgeUrl', $attributes);
        $this->assertEquals(false, $attributes['hasMatureContent']);
        $this->assertArrayHasKey('gamesCount', $attributes);
        $this->assertArrayHasKey('childHubsCount', $attributes);
        $this->assertArrayHasKey('parentHubsCount', $attributes);
        $this->assertArrayHasKey('createdAt', $attributes);
        $this->assertArrayHasKey('updatedAt', $attributes);
    }

    public function testItReturnsGamesCount(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $hub->games()->attach([$game1->id, $game2->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');
        $this->assertEquals(2, $attributes['gamesCount']);
    }

    public function testItReturnsChildHubsCount(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $parentHub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $childHub1 = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $childHub2 = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $parentHub->children()->attach([$childHub1->id, $childHub2->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$parentHub->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');
        $this->assertEquals(2, $attributes['childHubsCount']);
    }

    public function testItReturnsParentHubsCount(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $parentHub1 = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $parentHub2 = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $childHub = GameSet::factory()->create(['type' => GameSetType::Hub]);
        $parentHub1->children()->attach($childHub->id);
        $parentHub2->children()->attach($childHub->id);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$childHub->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');
        $this->assertEquals(2, $attributes['parentHubsCount']);
    }

    public function testItIncludesForumTopicLinkWhenPresent(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $forumTopic = ForumTopic::factory()->create();
        $hub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'forum_topic_id' => $forumTopic->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}");

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
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'forum_topic_id' => null,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}");

        // Assert
        $response->assertSuccessful();
        $links = $response->json('data.links');

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayNotHasKey('forumTopic', $links);
    }

    public function testItRejectsIncludeGamesOnIndex(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        GameSet::factory()->create(['type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/hubs?include=games');

        // Assert
        $response->assertStatus(400); // clients must use /hubs/{id}/games
    }

    public function testItRejectsIncludeGamesOnShow(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}?include=games");

        // Assert
        $response->assertStatus(400); // clients must use /hubs/{id}/games
    }

    public function testItRejectsIncludeChildrenOnShow(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}?include=children");

        // Assert
        $response->assertStatus(400); // clients must use /hubs/{id}/children
    }

    public function testItRejectsIncludeParentsOnShow(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $hub = GameSet::factory()->create(['type' => GameSetType::Hub]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('hubs')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/hubs/{$hub->id}?include=parents");

        // Assert
        $response->assertStatus(400); // clients must use /hubs/{id}/parents
    }
}
