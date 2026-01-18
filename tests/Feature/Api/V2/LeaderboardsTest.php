<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class LeaderboardsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'leaderboards';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/leaderboards';
    }

    protected function createResource(): Model
    {
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        return Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1, // visible leaderboard
        ]);
    }

    public function testItListsLeaderboards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => 'Fastest Time',
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'leaderboards', 'id' => (string) $leaderboard->id],
        ]);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        Leaderboard::factory()->count(100)->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));

        $this->assertGreaterThan(50, $response->json('meta.page.total'));
        $this->assertArrayHasKey('next', $response->json('links'));
    }

    public function testItFiltersByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        $leaderboard1 = Leaderboard::factory()->create([
            'game_id' => $game1->id,
            'order_column' => 1,
        ]);
        $leaderboard2 = Leaderboard::factory()->create([
            'game_id' => $game2->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards?filter[gameId]={$game1->id}");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $leaderboard1->id, $ids);
        $this->assertNotContains((string) $leaderboard2->id, $ids);
    }

    public function testItFiltersByState(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $activeLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => 1,
        ]);
        $disabledLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 2,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards?filter[state]=active');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $activeLeaderboard->id, $ids);
        $this->assertNotContains((string) $disabledLeaderboard->id, $ids);
    }

    public function testItFiltersByMultipleStates(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $activeLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => 1,
        ]);
        $disabledLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 2,
        ]);
        $unpublishedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Unpublished,
            'order_column' => 3,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards?filter[state]=active,disabled');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $activeLeaderboard->id, $ids);
        $this->assertContains((string) $disabledLeaderboard->id, $ids);
        $this->assertNotContains((string) $unpublishedLeaderboard->id, $ids);
    }

    public function testItFiltersByStateAll(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $activeLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => 1,
        ]);
        $disabledLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 2,
        ]);
        $unpublishedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Unpublished,
            'order_column' => 3,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards?filter[state]=all');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $activeLeaderboard->id, $ids);
        $this->assertContains((string) $disabledLeaderboard->id, $ids);
        $this->assertContains((string) $unpublishedLeaderboard->id, $ids);
    }

    public function testItExcludesHubGameLeaderboards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $gameSystem = System::factory()->create();
        System::factory()->create(['id' => System::Hubs]);

        $normalGame = Game::factory()->create(['system_id' => $gameSystem->id]);
        $hubGame = Game::factory()->create(['system_id' => System::Hubs]);

        $normalLeaderboard = Leaderboard::factory()->create([
            'game_id' => $normalGame->id,
            'order_column' => 1,
        ]);
        $hubLeaderboard = Leaderboard::factory()->create([
            'game_id' => $hubGame->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalLeaderboard->id, $ids);
        $this->assertNotContains((string) $hubLeaderboard->id, $ids);
    }

    public function testItExcludesEventGameLeaderboards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $gameSystem = System::factory()->create();
        System::factory()->create(['id' => System::Events]);

        $normalGame = Game::factory()->create(['system_id' => $gameSystem->id]);
        $eventGame = Game::factory()->create(['system_id' => System::Events]);

        $normalLeaderboard = Leaderboard::factory()->create([
            'game_id' => $normalGame->id,
            'order_column' => 1,
        ]);
        $eventLeaderboard = Leaderboard::factory()->create([
            'game_id' => $eventGame->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalLeaderboard->id, $ids);
        $this->assertNotContains((string) $eventLeaderboard->id, $ids);
    }

    public function testItExcludesHiddenLeaderboards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $visibleLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1, // visible
        ]);
        $hiddenLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => -1, // hidden
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $visibleLeaderboard->id, $ids);
        $this->assertNotContains((string) $hiddenLeaderboard->id, $ids);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $developer = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $developer->id,
            'title' => 'Fastest Time',
            'description' => 'Complete the game as fast as possible',
            'format' => 'TIME',
            'rank_asc' => true, // lower is better
            'state' => LeaderboardState::Active,
            'order_column' => 5,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Fastest Time', $attributes['title']);
        $this->assertEquals('Complete the game as fast as possible', $attributes['description']);
        $this->assertEquals('TIME', $attributes['format']);
        $this->assertEquals(true, $attributes['rankAsc']);
        $this->assertEquals('active', $attributes['state']);
        $this->assertEquals(5, $attributes['orderColumn']);
        $this->assertArrayHasKey('createdAt', $attributes);
        $this->assertArrayHasKey('updatedAt', $attributes);
    }

    public function testItCanIncludeGameRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Super Mario Bros.',
        ]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}?include=games");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'leaderboards',
            'id' => (string) $leaderboard->id,
        ]);

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals((string) $game->id, $included[0]['id']);
        $this->assertEquals('Super Mario Bros.', $included[0]['attributes']['title']);
    }

    public function testItCanIncludeDeveloperRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $developer = User::factory()->create(['display_name' => 'TestDeveloper']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $developer->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}?include=developer");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'leaderboards',
            'id' => (string) $leaderboard->id,
        ]);

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('users', $included[0]['type']);
        $this->assertEquals('TestDeveloper', $included[0]['attributes']['displayName']);
    }

    public function testItHandlesDeletedDeveloperAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $deletedUser = User::factory()->create(['display_name' => 'DeletedDev']);
        $deletedUser->delete();

        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $deletedUser->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}?include=developer");

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));
        $developerResource = $included->firstWhere('type', 'users');

        $this->assertNotNull($developerResource);
        $this->assertEquals('DeletedDev', $developerResource['attributes']['displayName']);
        $this->assertArrayHasKey('deletedAt', $developerResource['attributes']);
        $this->assertNotNull($developerResource['attributes']['deletedAt']);
        $this->assertNull($developerResource['attributes']['joinedAt']);
    }

    public function testItCanSortByOrderColumn(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 10]);
        Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 5]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards?sort=orderColumn');

        // Assert
        $response->assertSuccessful();
        $orderColumns = collect($response->json('data'))->pluck('attributes.orderColumn')->toArray();
        $this->assertLessThan($orderColumns[1], $orderColumns[0]);
    }

    public function testItCanSortByTitle(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        Leaderboard::factory()->create(['game_id' => $game->id, 'title' => 'Zelda Speedrun', 'order_column' => 1]);
        Leaderboard::factory()->create(['game_id' => $game->id, 'title' => 'Any% Run', 'order_column' => 2]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards?sort=title');

        // Assert
        $response->assertSuccessful();
        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertLessThan(0, strcmp($titles[0], $titles[1]));
    }
}
