<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\AchievementType;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class AchievementsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'achievements';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/achievements';
    }

    protected function createResource(): Model
    {
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        return Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    }

    public function testItListsAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'title' => 'Test Achievement',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'achievements', 'id' => (string) $achievement->id],
        ]);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->count(100)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));

        $this->assertGreaterThan(50, $response->json('meta.page.total'));
        $this->assertArrayHasKey('next', $response->json('links'));
    }

    public function testItDefaultsToPromotedOnly(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $promotedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'is_promoted' => true,
        ]);
        $unpromotedAchievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'is_promoted' => false,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $promotedAchievement->id, $ids);
        $this->assertNotContains((string) $unpromotedAchievement->id, $ids);
    }

    public function testItFiltersToUnpromotedOnly(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $promotedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'is_promoted' => true,
        ]);
        $unpromotedAchievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'is_promoted' => false,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements?filter[state]=unpromoted');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains((string) $promotedAchievement->id, $ids);
        $this->assertContains((string) $unpromotedAchievement->id, $ids);
    }

    public function testItFiltersToAllWhenStateAll(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $promotedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'is_promoted' => true,
        ]);
        $unpromotedAchievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'is_promoted' => false,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements?filter[state]=all');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $promotedAchievement->id, $ids);
        $this->assertContains((string) $unpromotedAchievement->id, $ids);
    }

    public function testItFiltersByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        // ... create achievement sets and attach them to games ...
        $achievementSet1 = AchievementSet::factory()->create();
        $achievementSet2 = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game1->id,
            'achievement_set_id' => $achievementSet1->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game2->id,
            'achievement_set_id' => $achievementSet2->id,
            'type' => AchievementSetType::Core,
        ]);

        // ... create achievements for each game ...
        // ... the AchievementCreated event automatically attaches them to their game's core set ...
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game1->id]);
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game2->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements?filter[gameId]={$game1->id}");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $achievement1->id, $ids);
        $this->assertNotContains((string) $achievement2->id, $ids);
    }

    public function testItFiltersByType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $progressionAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'type' => AchievementType::Progression,
        ]);
        $winConditionAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'type' => AchievementType::WinCondition,
        ]);
        $missableAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'type' => AchievementType::Missable,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements?filter[type]=progression');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $progressionAchievement->id, $ids);
        $this->assertNotContains((string) $winConditionAchievement->id, $ids);
        $this->assertNotContains((string) $missableAchievement->id, $ids);
    }

    public function testItFiltersByMultipleTypes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $progressionAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'type' => AchievementType::Progression,
        ]);
        $winConditionAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'type' => AchievementType::WinCondition,
        ]);
        $missableAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'type' => AchievementType::Missable,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements?filter[type]=progression,win_condition');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $progressionAchievement->id, $ids);
        $this->assertContains((string) $winConditionAchievement->id, $ids);
        $this->assertNotContains((string) $missableAchievement->id, $ids);
    }

    public function testItExcludesHubGameAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $gameSystem = System::factory()->create();
        System::factory()->create(['id' => System::Hubs]);

        $normalGame = Game::factory()->create(['system_id' => $gameSystem->id]);
        $hubGame = Game::factory()->create(['system_id' => System::Hubs]);

        $normalAchievement = Achievement::factory()->promoted()->create(['game_id' => $normalGame->id]);
        $hubAchievement = Achievement::factory()->promoted()->create(['game_id' => $hubGame->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalAchievement->id, $ids);
        $this->assertNotContains((string) $hubAchievement->id, $ids);
    }

    public function testItExcludesEventGameAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $gameSystem = System::factory()->create();
        System::factory()->create(['id' => System::Events]);

        $normalGame = Game::factory()->create(['system_id' => $gameSystem->id]);
        $eventGame = Game::factory()->create(['system_id' => System::Events]);

        $normalAchievement = Achievement::factory()->promoted()->create(['game_id' => $normalGame->id]);
        $eventAchievement = Achievement::factory()->promoted()->create(['game_id' => $eventGame->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $normalAchievement->id, $ids);
        $this->assertNotContains((string) $eventAchievement->id, $ids);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $developer = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'title' => 'Test Achievement',
            'description' => 'Test Description',
            'points' => 10,
            'type' => AchievementType::Progression,
            'is_promoted' => true,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Test Achievement', $attributes['title']);
        $this->assertEquals('Test Description', $attributes['description']);
        $this->assertEquals(10, $attributes['points']);
        $this->assertEquals('progression', $attributes['type']);
        $this->assertEquals('promoted', $attributes['state']);
        $this->assertArrayHasKey('pointsWeighted', $attributes);
        $this->assertArrayHasKey('badgeUrl', $attributes);
        $this->assertArrayHasKey('badgeLockedUrl', $attributes);
        $this->assertArrayHasKey('unlocksTotal', $attributes);
        $this->assertArrayHasKey('unlocksHardcore', $attributes);
        $this->assertArrayHasKey('unlockPercentage', $attributes);
        $this->assertArrayHasKey('unlockHardcorePercentage', $attributes);
        $this->assertArrayHasKey('createdAt', $attributes);
        $this->assertArrayHasKey('modifiedAt', $attributes);
        $this->assertArrayHasKey('orderColumn', $attributes);
    }

    public function testItCanIncludeAchievementSetRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        // ... the AchievementCreated event automatically attaches this to the core set ...
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}?include=achievementSet");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'achievements',
            'id' => (string) $achievement->id,
        ]);

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('achievement-sets', $included[0]['type']);
        $this->assertEquals((string) $achievementSet->id, $included[0]['id']);
    }

    public function testItCanIncludeDeveloperRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $developer = User::factory()->create(['display_name' => 'TestDeveloper']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}?include=developer");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'achievements',
            'id' => (string) $achievement->id,
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

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $deletedUser->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}?include=developer");

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

    public function testItCanIncludeGamesRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id, 'title' => 'Test Game']);
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        // ... the AchievementCreated event automatically attaches this to the core set ...
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}?include=games");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'achievements',
            'id' => (string) $achievement->id,
        ]);

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals((string) $game->id, $included[0]['id']);
    }

    public function testGamesRelationshipExcludesSubsetBackingGames(): void
    {
        /**
         * This test models a real scenario like Pokemon FireRed/LeafGreen [Subset - Shiny Pokemon].
         *
         * In prod, achievement set 8831 contains shiny-catching achievements:
         * - Game 24875 (the "subset game") links with type=Core (it "owns" the set).
         * - Games 515, 788 (FireRed/LeafGreen) link with type=Specialty (base games).
         *
         * The games relationship should return the base games (515, 788), NOT the "subset game" (24875).
         */

        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();

        $fireRed = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Pokemon FireRed Version',
        ]);
        $leafGreen = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Pokemon LeafGreen Version',
        ]);
        $shinySubset = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Pokemon FireRed Version | Pokemon LeafGreen Version [Subset - Shiny Pokemon+]',
        ]);

        $shinyAchievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $shinySubset->id, // linked to the subset "backing game" as core
            'achievement_set_id' => $shinyAchievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $fireRed->id,
            'achievement_set_id' => $shinyAchievementSet->id,
            'type' => AchievementSetType::Specialty,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $leafGreen->id,
            'achievement_set_id' => $shinyAchievementSet->id,
            'type' => AchievementSetType::Specialty,
        ]);

        $achievement = Achievement::factory()->promoted()->create(['game_id' => $shinySubset->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}?include=games");

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));
        $gameIds = $included->where('type', 'games')->pluck('id')->toArray();

        // ... the base games should be included ...
        $this->assertContains((string) $fireRed->id, $gameIds);
        $this->assertContains((string) $leafGreen->id, $gameIds);

        // ... the subset backing game should not be included ...
        $this->assertNotContains((string) $shinySubset->id, $gameIds);
    }

    public function testItCanSortByPoints(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 10]);
        Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 50]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements?sort=-points');

        // Assert
        $response->assertSuccessful();
        $points = collect($response->json('data'))->pluck('attributes.points')->toArray();
        $this->assertGreaterThan($points[1], $points[0]);
    }

    public function testItCanSortByTitle(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->create(['game_id' => $game->id, 'title' => 'Zelda Achievement']);
        Achievement::factory()->promoted()->create(['game_id' => $game->id, 'title' => 'Asteroids Achievement']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievements?sort=title');

        // Assert
        $response->assertSuccessful();
        $titles = collect($response->json('data'))->pluck('attributes.title')->toArray();
        $this->assertLessThan(0, strcmp($titles[0], $titles[1]));
    }

    public function testItReturnsOrderColumnFromPivot(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        /**
         * Create an achievement with order_column=42. The AchievementCreated event
         * automatically attaches it to the core set using this order_column value.
         */
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'order_column' => 42,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals(42, $response->json('data.attributes.orderColumn'));
    }
}
