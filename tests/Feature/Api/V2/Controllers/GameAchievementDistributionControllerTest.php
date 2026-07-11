<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Controllers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameAchievementDistributionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeds a game with one hardcore unlock from a ranked player and one from an
     * unranked player whose API key is 'unranked-api-key'.
     */
    private function createGameWithRankedAndUnrankedUnlocks(): Game
    {
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'players_total' => 5,
        ]);

        Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        $rankedPlayer = User::factory()->create();
        $unrankedPlayer = User::factory()->create(['web_api_key' => 'unranked-api-key']);
        $unrankedPlayer->update(['unranked_at' => now()]);

        PlayerGame::factory()->create([
            'user_id' => $rankedPlayer->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 1,
            'achievements_unlocked_softcore' => 0,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $unrankedPlayer->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 1,
            'achievements_unlocked_softcore' => 0,
        ]);

        return $game;
    }

    public function testItRequiresAuthentication(): void
    {
        // Act
        $response = $this->getJson('/api/v2/games/1/achievement-distribution');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItReturns404ForUnknownGame(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);

        // Act
        $response = $this->getJson('/api/v2/games/999999/achievement-distribution', [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertNotFound();
        $response->assertJsonPath('errors.0.status', '404');
        $response->assertJsonPath('errors.0.title', 'Not Found');
        $response->assertJsonPath('errors.0.detail', 'No game found with ID 999999.');
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function testItReturnsPromotedDistribution(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'players_total' => 10,
        ]);

        Achievement::factory()->promoted()->count(3)->create(['game_id' => $game->id]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        PlayerGame::factory()->create([
            'user_id' => $userA->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 3,
            'achievements_unlocked_softcore' => 3,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $userB->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 0,
            'achievements_unlocked_softcore' => 2,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $userC->id,
            'game_id' => $game->id,
            'achievements_unlocked_hardcore' => 1,
            'achievements_unlocked_softcore' => 1,
        ]);

        // Act
        $response = $this->getJson("/api/v2/games/{$game->id}/achievement-distribution", [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals([
            'promoted' => [
                'totalAchievements' => 3,
                'distribution' => [
                    ['unlockCount' => 1, 'playersHardcore' => 1, 'playersCasual' => 1],
                    ['unlockCount' => 2, 'playersHardcore' => 0, 'playersCasual' => 1],
                    ['unlockCount' => 3, 'playersHardcore' => 1, 'playersCasual' => 1],
                ],
            ],
            'unpromoted' => [
                'totalAchievements' => 0,
                'distribution' => [],
            ],
        ], $response->json('meta'));
    }

    public function testItReturnsUnpromotedDistribution(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'players_total' => 10,
        ]);

        $achievements = Achievement::factory()->count(2)->create([
            'game_id' => $game->id,
            'is_promoted' => false,
        ]);

        $hardcorePlayer = User::factory()->create();
        $casualPlayer = User::factory()->create();

        foreach ($achievements as $achievement) {
            PlayerAchievement::factory()->hardcore()->create([
                'user_id' => $hardcorePlayer->id,
                'achievement_id' => $achievement->id,
            ]);
        }

        PlayerAchievement::factory()->create([
            'user_id' => $casualPlayer->id,
            'achievement_id' => $achievements->first()->id,
            'unlocked_hardcore_at' => null,
        ]);

        // Act
        $response = $this->getJson("/api/v2/games/{$game->id}/achievement-distribution", [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals([
            'promoted' => [
                'totalAchievements' => 0,
                'distribution' => [],
            ],
            'unpromoted' => [
                'totalAchievements' => 2,
                'distribution' => [
                    ['unlockCount' => 1, 'playersHardcore' => 0, 'playersCasual' => 1],
                    ['unlockCount' => 2, 'playersHardcore' => 1, 'playersCasual' => 0],
                ],
            ],
        ], $response->json('meta'));
    }

    public function testItExcludesUnrankedUsersFromDistribution(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $game = $this->createGameWithRankedAndUnrankedUnlocks();

        // Act
        $response = $this->getJson("/api/v2/games/{$game->id}/achievement-distribution", [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals([
            ['unlockCount' => 1, 'playersHardcore' => 1, 'playersCasual' => 0],
        ], $response->json('meta.promoted.distribution'));
    }

    public function testItIncludesTheUnrankedRequesterInTheirOwnDistribution(): void
    {
        // Arrange
        $game = $this->createGameWithRankedAndUnrankedUnlocks();

        // Act
        $response = $this->getJson("/api/v2/games/{$game->id}/achievement-distribution", [
            'X-API-Key' => 'unranked-api-key',
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals([
            ['unlockCount' => 1, 'playersHardcore' => 2, 'playersCasual' => 0],
        ], $response->json('meta.promoted.distribution'));
    }

    public function testItReturnsEmptyDistributionsWhenGameHasNoAchievements(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        // Act
        $response = $this->getJson("/api/v2/games/{$game->id}/achievement-distribution", [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals([
            'promoted' => [
                'totalAchievements' => 0,
                'distribution' => [],
            ],
            'unpromoted' => [
                'totalAchievements' => 0,
                'distribution' => [],
            ],
        ], $response->json('meta'));
        $this->assertSame([], $response->json('meta.promoted.distribution'));
        $this->assertSame([], $response->json('meta.unpromoted.distribution'));
        $this->assertStringContainsString('"distribution":[]', $response->getContent());
    }
}
