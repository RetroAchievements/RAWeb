<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class PlayerAchievementsTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $user = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->get("/api/v2/users/{$user->ulid}/player-achievements");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesPlayerAchievementsForUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement1 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement2 = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        $pa1 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
        ]);
        $pa2 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $pa1->id, $ids);
        $this->assertContains((string) $pa2->id, $ids);
    }

    public function testItReturns404ForNonexistentUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/nonexistent-user/player-achievements');

        // Assert
        $response->assertNotFound();
    }

    public function testItExcludesHubGameAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $regularSystem = System::factory()->create();
        System::factory()->create(['id' => System::Hubs]);

        $regularGame = Game::factory()->create(['system_id' => $regularSystem->id]);
        $hubGame = Game::factory()->create(['system_id' => System::Hubs]);

        $regularAchievement = Achievement::factory()->create(['game_id' => $regularGame->id]);
        $hubAchievement = Achievement::factory()->create(['game_id' => $hubGame->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $regularAchievement->id,
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $hubAchievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function testItExcludesEventGameAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $regularSystem = System::factory()->create();
        System::factory()->create(['id' => System::Events]);

        $regularGame = Game::factory()->create(['system_id' => $regularSystem->id]);
        $eventGame = Game::factory()->create(['system_id' => System::Events]);

        $regularAchievement = Achievement::factory()->create(['game_id' => $regularGame->id]);
        $eventAchievement = Achievement::factory()->create(['game_id' => $eventGame->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $regularAchievement->id,
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $eventAchievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function testItSortsByUnlockedAtDescendingByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $player = User::factory()->create();

        $achievement1 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement2 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement3 = Achievement::factory()->create(['game_id' => $game->id]);

        $pa1 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
            'unlocked_at' => now()->subDays(3),
        ]);
        $pa2 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
            'unlocked_at' => now()->subDay(),
        ]);
        $pa3 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement3->id,
            'unlocked_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertEquals([
            (string) $pa3->id, // !! most recently unlocked comes first
            (string) $pa2->id,
            (string) $pa1->id,
        ], $ids);
    }

    public function testItCanFilterByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $achievement1 = Achievement::factory()->create(['game_id' => $game1->id]);

        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $achievement2 = Achievement::factory()->create(['game_id' => $game2->id]);

        $player = User::factory()->create();
        $pa1 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?filter[gameId]={$game1->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pa1->id, $data[0]['id']);
    }

    public function testItCanFilterByAchievementSetId(): void
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

        // ... achievement creation auto-syncs all game achievements into the base set ...
        $coreAchievement = Achievement::factory()->create(['game_id' => $game->id]);
        $bonusAchievement = Achievement::factory()->create(['game_id' => $game->id]);
        AchievementSetAchievement::create([
            'achievement_set_id' => $bonusSet->id,
            'achievement_id' => $bonusAchievement->id,
        ]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $coreAchievement->id,
        ]);
        $paBonusAchievement = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $bonusAchievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?filter[achievementSetId]={$bonusSet->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $paBonusAchievement->id, $data[0]['id']);
    }

    public function testItCanFilterByUnlockedFrom(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement1 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement2 = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
            'unlocked_at' => '2024-01-01 12:00:00',
        ]);
        $pa2 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
            'unlocked_at' => '2024-06-15 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?filter[unlockedFrom]=2024-03-01");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pa2->id, $data[0]['id']);
    }

    public function testItCanFilterByUnlockedTo(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement1 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement2 = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        $pa1 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
            'unlocked_at' => '2024-01-01 12:00:00',
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
            'unlocked_at' => '2024-06-15 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?filter[unlockedTo]=2024-03-01");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pa1->id, $data[0]['id']);
    }

    public function testItCanFilterByUnlockedFromAndUnlockedToTogether(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement1 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement2 = Achievement::factory()->create(['game_id' => $game->id]);
        $achievement3 = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
            'unlocked_at' => '2024-01-01 12:00:00',
        ]);
        $pa2 = PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
            'unlocked_at' => '2024-06-15 12:00:00',
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement3->id,
            'unlocked_at' => '2024-12-01 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?filter[unlockedFrom]=2024-03-01&filter[unlockedTo]=2024-09-01");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $pa2->id, $data[0]['id']);
    }

    public function testItCanIncludeAchievementRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'title' => 'Beat the Boss',
        ]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?include=achievement");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('achievements', $included[0]['type']);
        $this->assertEquals('Beat the Boss', $included[0]['attributes']['title']);
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
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements?include=game");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals('Super Mario Bros.', $included[0]['attributes']['title']);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $player = User::factory()->create();
        $achievements = Achievement::factory()->count(60)->create(['game_id' => $game->id]);
        foreach ($achievements as $index => $achievement) {
            PlayerAchievement::factory()->create([
                'user_id' => $player->id,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now()->subMinutes($index),
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(60, $response->json('meta.page.total'));
    }

    public function testItDoesNotIncludeSelfLinks(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItReturnsTimestampAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now()->subDays(5),
            'unlocked_hardcore_at' => now()->subDays(4),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');
        $this->assertNotNull($attributes['unlockedAt']);
        $this->assertNotNull($attributes['unlockedHardcoreAt']);
    }

    public function testItFetchesPlayerAchievementsForAchievement(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $pa1 = PlayerAchievement::factory()->create([
            'user_id' => $player1->id,
            'achievement_id' => $achievement->id,
        ]);
        $pa2 = PlayerAchievement::factory()->create([
            'user_id' => $player2->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $pa1->id, $ids);
        $this->assertContains((string) $pa2->id, $ids);
    }

    public function testItExcludesUnrankedUsersFromAchievementRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $rankedPlayer = User::factory()->create();
        $unrankedPlayer = User::factory()->create(['unranked_at' => now()]);
        UnrankedUser::factory()->create(['user_id' => $unrankedPlayer->id]);

        PlayerAchievement::factory()->create([
            'user_id' => $rankedPlayer->id,
            'achievement_id' => $achievement->id,
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $unrankedPlayer->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function testItDoesNotExcludeUnrankedUsersFromUserRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $unrankedPlayer = User::factory()->create(['unranked_at' => now()]);
        UnrankedUser::factory()->create(['user_id' => $unrankedPlayer->id]);

        PlayerAchievement::factory()->create([
            'user_id' => $unrankedPlayer->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$unrankedPlayer->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function testItCanIncludeUserRelationshipFromAchievement(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/player-achievements?include=user");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('users', $included[0]['type']);
        $this->assertEquals($player->display_name, $included[0]['attributes']['displayName']);
    }

    public function testItCanIncludeGameRelationshipFromAchievement(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/player-achievements?include=game");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals('Sonic the Hedgehog', $included[0]['attributes']['title']);
    }

    public function testItAlwaysIncludesAchievementAndUserResourceIdentifiers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        // ... fetch without ?include= from the user side ...
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        // ... resource identifiers should still be present ...
        $response->assertSuccessful();
        $relationships = $response->json('data.0.relationships');

        $this->assertArrayHasKey('achievement', $relationships);
        $this->assertEquals('achievements', $relationships['achievement']['data']['type']);
        $this->assertEquals((string) $achievement->id, $relationships['achievement']['data']['id']);

        $this->assertArrayHasKey('user', $relationships);
        $this->assertEquals('users', $relationships['user']['data']['type']);
        $this->assertEquals($player->ulid, $relationships['user']['data']['id']);
    }

    public function testItAlwaysIncludesResourceIdentifiersFromAchievementSide(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        // ... fetch without ?include= from the achievement side ...
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/player-achievements");

        // Assert
        // ... resource identifiers should still be present ...
        $response->assertSuccessful();
        $relationships = $response->json('data.0.relationships');

        $this->assertArrayHasKey('achievement', $relationships);
        $this->assertEquals('achievements', $relationships['achievement']['data']['type']);
        $this->assertEquals((string) $achievement->id, $relationships['achievement']['data']['id']);

        $this->assertArrayHasKey('user', $relationships);
        $this->assertEquals('users', $relationships['user']['data']['type']);
        $this->assertEquals($player->ulid, $relationships['user']['data']['id']);
    }

    public function testItDoesNotExposeInternalFields(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-achievements')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-achievements");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');
        $this->assertArrayNotHasKey('unlockerId', $attributes);
        $this->assertArrayNotHasKey('triggerId', $attributes);
        $this->assertArrayNotHasKey('playerSessionId', $attributes);
        $this->assertArrayNotHasKey('unlockedEffectiveAt', $attributes);
    }
}
