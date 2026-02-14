<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class PlayerAchievementSetsTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        $user = User::factory()->create();
        PlayerAchievementSet::factory()->create([
            'user_id' => $user->id,
            'achievement_set_id' => $achievementSet->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->get("/api/v2/users/{$user->ulid}/player-achievement-sets");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesPlayerAchievementSetsForUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $coreSet = AchievementSet::factory()->create();
        $bonusSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $coreSet->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $bonusSet->id,
            'type' => AchievementSetType::Bonus,
        ]);

        $player = User::factory()->create();
        $pas1 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $coreSet->id,
            'last_unlock_hardcore_at' => now()->subDay(),
        ]);
        $pas2 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $bonusSet->id,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $pas1->id, $ids);
        $this->assertContains((string) $pas2->id, $ids);
    }

    public function testItReturns404ForNonexistentUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/nonexistent-user/player-achievement-sets');

        // Assert
        $response->assertNotFound();
    }

    public function testItSortsByLastUnlockAtByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $set1 = AchievementSet::factory()->create();
        $set2 = AchievementSet::factory()->create();
        $set3 = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $set2->id,
            'type' => AchievementSetType::Bonus,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $set3->id,
            'type' => AchievementSetType::Bonus,
        ]);

        $pas1 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set1->id,
            'last_unlock_at' => now()->subDays(3),
        ]);
        $pas2 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set2->id,
            'last_unlock_at' => now()->subDay(),
        ]);
        $pas3 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set3->id,
            'last_unlock_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertEquals([
            (string) $pas3->id,
            (string) $pas2->id,
            (string) $pas1->id,
        ], $ids);
    }

    public function testItCanFilterByAchievementSetId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $set1 = AchievementSet::factory()->create();
        $set2 = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $set2->id,
            'type' => AchievementSetType::Bonus,
        ]);

        $pas1 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set1->id,
            'last_unlock_hardcore_at' => now(),
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set2->id,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets?filter[achievementSetId]={$set1->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pas1->id, $data[0]['id']);
    }

    public function testItCanFilterByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        $set1 = AchievementSet::factory()->create();
        $set2 = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game1->id,
            'achievement_set_id' => $set1->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game2->id,
            'achievement_set_id' => $set2->id,
            'type' => AchievementSetType::Core,
        ]);

        $pas1 = PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set1->id,
            'last_unlock_hardcore_at' => now(),
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $set2->id,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets?filter[gameId]={$game1->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pas1->id, $data[0]['id']);
    }

    public function testItCanIncludeAchievementSetRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 25,
            'points_total' => 500,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $achievementSet->id,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets?include=achievementSet");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('achievement-sets', $included[0]['type']);
        $this->assertEquals(25, $included[0]['attributes']['achievementsPublished']);
    }

    public function testItCanIncludeGameRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $achievementSet->id,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets?include=game");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals('Sonic the Hedgehog', $included[0]['attributes']['title']);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $achievementSets = AchievementSet::factory()->count(60)->create();
        foreach ($achievementSets as $index => $achievementSet) {
            GameAchievementSet::factory()->create([
                'game_id' => $game->id,
                'achievement_set_id' => $achievementSet->id,
                'type' => AchievementSetType::Bonus,
            ]);
            PlayerAchievementSet::factory()->create([
                'user_id' => $player->id,
                'achievement_set_id' => $achievementSet->id,
                'last_unlock_hardcore_at' => now()->subMinutes($index),
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(60, $response->json('meta.page.total'));
    }

    public function testItReturnsProgressAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $achievementSet->id,
            'achievements_unlocked' => 15,
            'achievements_unlocked_hardcore' => 12,
            'achievements_unlocked_softcore' => 3,
            'points' => 300,
            'points_hardcore' => 240,
            'points_weighted' => 600,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertEquals(15, $attributes['achievementsUnlocked']);
        $this->assertEquals(12, $attributes['achievementsUnlockedHardcore']);
        $this->assertEquals(3, $attributes['achievementsUnlockedSoftcore']);
        $this->assertEquals(300, $attributes['points']);
        $this->assertEquals(240, $attributes['pointsHardcore']);
        $this->assertEquals(600, $attributes['pointsWeighted']);
    }

    public function testItReturnsCompletionTimestamps(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $achievementSet->id,
            'completion_percentage' => 0.75,
            'completion_percentage_hardcore' => 0.60,
            'completed_at' => now()->subDays(2),
            'completed_hardcore_at' => now()->subDay(),
            'last_unlock_at' => now()->subHours(6),
            'last_unlock_hardcore_at' => now()->subHours(3),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertNotNull($attributes['completedAt']);
        $this->assertNotNull($attributes['completedHardcoreAt']);
        $this->assertNotNull($attributes['lastUnlockAt']);
        $this->assertNotNull($attributes['lastUnlockHardcoreAt']);
        $this->assertEquals(0.75, $attributes['completionPercentage']);
        $this->assertEquals(0.60, $attributes['completionPercentageHardcore']);
    }

    public function testItReturnsTimeTrackingAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $achievementSet->id,
            'time_taken' => 7200,
            'time_taken_hardcore' => 5400,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertEquals(7200, $attributes['timeTaken']);
        $this->assertEquals(5400, $attributes['timeTakenHardcore']);
    }

    public function testItDoesNotIncludeSelfLinks(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $player = User::factory()->create();

        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $achievementSet->id,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievement-sets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievement-sets");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItCanBeIncludedOnPlayerGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $coreSet = AchievementSet::factory()->create();
        $bonusSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $coreSet->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $bonusSet->id,
            'type' => AchievementSetType::Bonus,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'last_played_at' => now(),
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $coreSet->id,
            'achievements_unlocked_hardcore' => 10,
            'last_unlock_hardcore_at' => now(),
        ]);
        PlayerAchievementSet::factory()->create([
            'user_id' => $player->id,
            'achievement_set_id' => $bonusSet->id,
            'achievements_unlocked_hardcore' => 5,
            'last_unlock_hardcore_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?include=playerAchievementSets");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertCount(2, $included);

        $types = collect($included)->pluck('type')->unique()->toArray();
        $this->assertEquals(['player-achievement-sets'], $types);
    }
}
