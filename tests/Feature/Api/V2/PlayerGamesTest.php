<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Enums\AwardType;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testItCanFilterByMultipleGameIds(): void
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
            'last_played_at' => now(),
        ]);
        $pg2 = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'last_played_at' => now(),
        ]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game3->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?filter[gameId]={$game1->id},{$game2->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $pg1->id, $ids);
        $this->assertContains((string) $pg2->id, $ids);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function awardKindProvider(): array
    {
        return [
            'beaten casual' => ['beaten-casual', 'beaten_at'],
            'beaten hardcore' => ['beaten-hardcore', 'beaten_hardcore_at'],
        ];
    }

    #[DataProvider('awardKindProvider')]
    public function testItCanFilterByAwardKind(string $awardKind, string $timestampColumn): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        $milestonePg = match ($timestampColumn) {
            'beaten_at' => PlayerGame::factory()->create([
                'user_id' => $player->id,
                'game_id' => $game1->id,
                'beaten_at' => now()->subDay(),
                'last_played_at' => now(),
            ]),
            'beaten_hardcore_at' => PlayerGame::factory()->create([
                'user_id' => $player->id,
                'game_id' => $game1->id,
                'beaten_hardcore_at' => now()->subDay(),
                'last_played_at' => now(),
            ]),
            default => throw new InvalidArgumentException("Unsupported timestamp column [{$timestampColumn}]."),
        };
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?filter[awardKind]={$awardKind}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $milestonePg->id, $data[0]['id']);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function masteryAwardKindProvider(): array
    {
        return [
            'completion implies beaten casual' => ['beaten-casual', UnlockMode::Casual],
            'mastery implies beaten hardcore' => ['beaten-hardcore', UnlockMode::Hardcore],
        ];
    }

    #[DataProvider('masteryAwardKindProvider')]
    public function testItIncludesMasteryAwardsWithoutBeatenTimestamps(string $awardKind, int $awardTier): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $playerGame = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game->id,
            'beaten_at' => null,
            'beaten_hardcore_at' => null,
            'last_played_at' => now(),
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => $awardTier,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?filter[awardKind]={$awardKind}");

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals((string) $playerGame->id, $response->json('data.0.id'));
    }

    public function testItCanFilterByMultipleAwardKinds(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $game3 = Game::factory()->create(['system_id' => $system->id]);

        $beatenCasualPg = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game1->id,
            'beaten_at' => now()->subDay(),
            'last_played_at' => now(),
        ]);
        $beatenHardcorePg = PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game2->id,
            'beaten_hardcore_at' => now()->subDay(),
            'last_played_at' => now(),
        ]);
        PlayerGame::factory()->create([
            'user_id' => $player->id,
            'game_id' => $game3->id,
            'last_played_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('player-games')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/player-games?filter[awardKind]=beaten-casual,beaten-hardcore");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $beatenCasualPg->id, $ids);
        $this->assertContains((string) $beatenHardcorePg->id, $ids);
    }

    public function testItRejectsInvalidAwardKindValues(): void
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
            ->get("/api/v2/users/{$player->ulid}/player-games?filter[awardKind]=bogus");

        // Assert
        $response->assertStatus(400);
        $this->assertEquals('invalid_filter', $response->json('errors.0.code'));
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

    public function testItCanIncludeAchievementSetsRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $player = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $coreSet = AchievementSet::factory()->create(['achievements_published' => 50, 'points_total' => 1000]);
        $bonusSet = AchievementSet::factory()->create(['achievements_published' => 10, 'points_total' => 200]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $coreSet->id,
            'type' => AchievementSetType::Core,
        ]);
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $bonusSet->id,
            'type' => AchievementSetType::Bonus,
            'title' => 'Bonus Challenges',
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
            ->get("/api/v2/users/{$player->ulid}/player-games?include=achievementSets");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertCount(2, $included);

        $types = collect($included)->pluck('type')->unique()->toArray();
        $this->assertEquals(['achievement-sets'], $types);

        $ids = collect($included)->pluck('id')->toArray();
        $this->assertContains((string) $coreSet->id, $ids);
        $this->assertContains((string) $bonusSet->id, $ids);
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
            'playtime_total' => 3600,
            'time_to_beat' => 1800,
            'time_to_beat_hardcore' => 1500,
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
        $this->assertEquals(3600, $attributes['playtimeTotalSeconds']);
        $this->assertEquals(1800, $attributes['timeToBeatSeconds']);
        $this->assertEquals(1500, $attributes['timeToBeatHardcoreSeconds']);
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
