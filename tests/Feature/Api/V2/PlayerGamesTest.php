<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class PlayerGamesTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $user = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->get("/api/v2/users/{$user->ulid}/player-games");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesPlayerGamesForUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        $player = User::factory()->create();
        $pg1 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game1->id,
            'last_played_at' => now()->subDay(),
        ]);
        $pg2 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $pg1->id, $ids);
        $this->assertContains((string) $pg2->id, $ids);
    }

    public function testItReturns404ForNonexistentUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/nonexistent-user/player-games');

        // Assert
        $response->assertNotFound();
    }

    public function testItExcludesHubGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $regularSystem = System::factory()->create();
        System::factory()->create(['id' => System::Hubs]);

        $player = User::factory()->create();

        $regularGame = Game::factory()->create(['system_id' => $regularSystem->id]);
        $hubGame = Game::factory()->create(['system_id' => System::Hubs]);

        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $regularGame->id,
            'last_played_at' => now(),
        ]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $hubGame->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function testItExcludesEventGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $regularSystem = System::factory()->create();
        System::factory()->create(['id' => System::Events]);

        $player = User::factory()->create();

        $regularGame = Game::factory()->create(['system_id' => $regularSystem->id]);
        $eventGame = Game::factory()->create(['system_id' => System::Events]);

        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $regularGame->id,
            'last_played_at' => now(),
        ]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $eventGame->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function testItSortsByLastPlayedAtByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $game3 = Game::factory()->create(['system_id' => $system->id]);

        $pg1 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game1->id,
            'last_played_at' => now()->subDays(3),
        ]);
        $pg2 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'last_played_at' => now()->subDay(),
        ]);
        $pg3 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game3->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertEquals([
            (string) $pg3->id, // most recently played comes first
            (string) $pg2->id,
            (string) $pg1->id,
        ], $ids);
    }

    public function testItCanFilterByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        $pg1 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game1->id,
            'last_played_at' => now(),
        ]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?filter[gameId]={$game1->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pg1->id, $data[0]['id']);
    }

    public function testItCanIncludeGameRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Super Mario Bros.',
        ]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?include=game");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals('Super Mario Bros.', $included[0]['attributes']['title']);
    }

    public function testItCanIncludeUserRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create(['display_name' => 'TestPlayer']);

        $game = Game::factory()->create(['system_id' => $system->id]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?include=user");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('users', $included[0]['type']);
        $this->assertEquals('TestPlayer', $included[0]['attributes']['displayName']);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $games = Game::factory()->count(60)->create(['system_id' => $system->id]);
        foreach ($games as $index => $game) {
            PlayerGame::factory()->create([
                'user_id' => $player->id,
                'game_id' => $game->id,
                'last_played_at' => now()->subMinutes($index),
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(60, $response->json('meta.page.total'));
    }

    public function testItReturnsCorrectCoreAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'achievements_total' => 50,
            'achievements_unlocked' => 30,
            'achievements_unlocked_hardcore' => 25,
            'achievements_unlocked_softcore' => 5,
            'points_total' => 1000,
            'points' => 600,
            'points_hardcore' => 500,
            'points_weighted' => 1200,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertEquals(50, $attributes['coreAchievementsTotal']);
        $this->assertEquals(30, $attributes['coreAchievementsUnlocked']);
        $this->assertEquals(25, $attributes['coreAchievementsUnlockedHardcore']);
        $this->assertEquals(5, $attributes['coreAchievementsUnlockedSoftcore']);
        $this->assertEquals(1000, $attributes['corePointsTotal']);
        $this->assertEquals(600, $attributes['corePoints']);
        $this->assertEquals(500, $attributes['corePointsHardcore']);
        $this->assertEquals(1200, $attributes['corePointsWeighted']);
    }

    public function testItReturnsCorrectAllSetsAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'all_achievements_total' => 75,
            'all_achievements_unlocked' => 40,
            'all_achievements_unlocked_hardcore' => 35,
            'all_points_total' => 1500,
            'all_points' => 800,
            'all_points_hardcore' => 700,
            'all_points_weighted' => 1800,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertEquals(75, $attributes['achievementsTotal']);
        $this->assertEquals(40, $attributes['achievementsUnlocked']);
        $this->assertEquals(35, $attributes['achievementsUnlockedHardcore']);
        $this->assertEquals(1500, $attributes['pointsTotal']);
        $this->assertEquals(800, $attributes['points']);
        $this->assertEquals(700, $attributes['pointsHardcore']);
        $this->assertEquals(1800, $attributes['pointsWeighted']);
    }

    public function testItReturnsMilestoneTimestamps(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'beaten_at' => now()->subDays(5),
            'beaten_hardcore_at' => now()->subDays(4),
            'completed_at' => now()->subDays(2),
            'completed_hardcore_at' => now()->subDay(),
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertNotNull($attributes['beatenAt']);
        $this->assertNotNull($attributes['beatenHardcoreAt']);
        $this->assertNotNull($attributes['coreCompletedAt']);
        $this->assertNotNull($attributes['coreCompletedHardcoreAt']);
    }

    public function testItDoesNotIncludeSelfLinks(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItDoesNotIncludeDeletedPlayerGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game1->id,
            'last_played_at' => now(),
        ]);
        $deletedPg = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'last_played_at' => now(),
        ]);
        $deletedPg->delete();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertNotContains((string) $deletedPg->id, $ids);
    }
}
