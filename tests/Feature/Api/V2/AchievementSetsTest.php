<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Database\Eloquent\Model;

class AchievementSetsTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'achievement-sets';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/achievement-sets';
    }

    protected function createResource(): Model
    {
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        return $achievementSet;
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create([
            'points_total' => 500,
            'points_weighted' => 1000,
            'achievements_published' => 50,
            'achievements_unpublished' => 5,
        ]);

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-sets/{$achievementSet->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals(500, $attributes['pointsTotal']);
        $this->assertEquals(1000, $attributes['pointsWeighted']);
        $this->assertEquals(50, $attributes['achievementsPublished']);
        $this->assertEquals(5, $attributes['achievementsUnpublished']);
        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('badgeUrl', $attributes);
        $this->assertArrayHasKey('gameIds', $attributes);
        $this->assertArrayHasKey('type', $attributes);
        $this->assertArrayHasKey('achievementsFirstPublishedAt', $attributes);
        $this->assertArrayHasKey('createdAt', $attributes);
        $this->assertArrayHasKey('updatedAt', $attributes);
        $this->assertArrayNotHasKey('playersTotal', $attributes);
        $this->assertArrayNotHasKey('playersHardcore', $attributes);
    }

    public function testItCanIncludeGamesRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Test Game',
            'system_id' => $system->id,
        ]);
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-sets/{$achievementSet->id}?include=games");

        // Assert
        $response->assertSuccessful();

        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals((string) $game->id, $included[0]['id']);
    }

    public function testTypeAttributeIsNullWhenAccessedDirectly(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $achievementSet = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-sets/{$achievementSet->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        // ... types come from game_achievement_sets - with no game, we have no type ...
        $this->assertNull($attributes['type']);
    }

    public function testTypeAttributeIsPresentWhenIncludedViaGame(): void
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

        // Act
        $response = $this->jsonApi('v2')
            ->expects('games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}?include=achievementSets");

        // Assert
        $response->assertSuccessful();

        $included = $response->json('included');
        $this->assertNotEmpty($included);

        $achievementSetData = collect($included)->firstWhere('type', 'achievement-sets');
        $this->assertNotNull($achievementSetData);
        $this->assertEquals('core', $achievementSetData['attributes']['type']);
    }

    public function testItReturnsGameIdsAttribute(): void
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

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-sets/{$achievementSet->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertArrayHasKey('gameIds', $attributes);
        $this->assertEquals([$game->id], $attributes['gameIds']);
    }

    public function testItExcludesSubsetBackingGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();

        $actualGame = Game::factory()->create([
            'title' => 'Pokemon Red',
            'system_id' => $system->id,
        ]);
        $subsetGame = Game::factory()->create([
            'title' => 'Pokemon Red [Subset - Professor Oak Challenge]',
            'system_id' => $system->id,
        ]);

        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $actualGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Bonus,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $subsetGame->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-sets/{$achievementSet->id}?include=games");

        // Assert
        $response->assertSuccessful();

        // ... the gameIds attribute should exclude the backing game ...
        $attributes = $response->json('data.attributes');
        $this->assertEquals([$actualGame->id], $attributes['gameIds']);

        // ... the games relationship should also exclude the backing game ...
        $included = $response->json('included');
        $includedIds = collect($included)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($actualGame->id, $includedIds);
        $this->assertNotContains($subsetGame->id, $includedIds);
    }
}
