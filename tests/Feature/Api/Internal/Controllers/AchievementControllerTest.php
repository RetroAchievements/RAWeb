<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Internal\Controllers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AchievementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsUnauthorizedWhenNoApiKeyIsProvided(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => 'DevCompliance',
                ],
            ],
        ]);

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function testItReturnsForbiddenWhenUserIsNotServiceAccount(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'regular-user-api-key']);
        $achievement = Achievement::factory()->create();

        // ... this user is not in the allowed service accounts list ...
        config(['internal-api.allowed_user_ids' => '99999']);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => 'DevCompliance',
                ],
            ],
        ], [
            'X-API-Key' => 'regular-user-api-key',
        ]);

        // Assert
        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Unauthorized',
            'errors' => [
                [
                    'status' => '403',
                    'code' => 'forbidden',
                    'title' => 'Unauthorized',
                ],
            ],
        ]);
    }

    public function testItSuccessfullyDemotesAnAchievement(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);

        // ... this is an actual service account ...
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create([
            'User' => 'DevCompliance',
            'display_name' => 'DevCompliance',
        ]);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'achievementId',
                    'status',
                    'demotedAt',
                    'demotedBy',
                    'wasTitleUpdated',
                ],
            ],
        ]);
        $response->assertJsonPath('data.attributes.status', 'demoted');
        $response->assertJsonPath('data.attributes.demotedBy', 'DevCompliance');
        $response->assertJsonPath('data.attributes.wasTitleUpdated', false);

        $achievement->refresh();
        $this->assertEquals(AchievementFlag::Unofficial->value, $achievement->Flags); // !! demoted
    }

    public function testItSuccessfullyDemotesAnAchievementWithTitleChange(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create([
            'User' => 'DevCompliance',
            'display_name' => 'DevCompliance',
        ]);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Title' => 'Original Title',
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => $demotingUser->username,
                    'title' => 'DEMOTED AS UNWELCOME CONCEPT - Original Title', // !! new title
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.attributes.wasTitleUpdated', true);

        $achievement->refresh();
        $this->assertEquals(AchievementFlag::Unofficial->value, $achievement->Flags);
        $this->assertEquals('DEMOTED AS UNWELCOME CONCEPT - Original Title', $achievement->title); // !!
    }

    public function testItReturnsValidationErrorForMissingAchievementId(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'username' => 'DevCompliance',
                    // ... no achievementId ...
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.status', '422');
        $response->assertJsonPath('errors.0.code', 'validation_error');
        $response->assertJsonPath('errors.0.title', 'The given data was invalid');
        $response->assertJsonPath('errors.0.detail', 'The data.attributes.achievementId field is required.');
    }

    public function testItReturnsValidationErrorForMissingUsername(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    // ... no username ...
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'The data.attributes.username field is required.');
    }

    public function testItReturnsErrorForNonExistentAchievement(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create(['User' => 'DevCompliance']);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => 999999, // !!
                    'username' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.status', '422');
        $response->assertJsonPath('errors.0.code', 'validation_error');
        $response->assertJsonPath('errors.0.title', 'The given data was invalid');
        $response->assertJsonPath('errors.0.detail', 'The specified achievement does not exist.');
    }

    public function testItReturnsErrorForNonExistentUsername(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => 'NonExistentUser', // !!
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.code', 'validation_error');
        $response->assertJsonPath('errors.0.title', 'The given data was invalid');
        $response->assertJsonPath('errors.0.detail', 'The specified username does not exist.');
    }

    public function testItIsIdempotentWhenDemotingAlreadyDemotedAchievement(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create([
            'User' => 'DevCompliance',
            'display_name' => 'DevCompliance',
        ]);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Flags' => AchievementFlag::Unofficial->value, // !! already unofficial
        ]);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertOk(); // we didn't crash
        $response->assertJsonPath('data.attributes.status', 'demoted');

        $achievement->refresh();
        $this->assertEquals(AchievementFlag::Unofficial->value, $achievement->Flags); // still unofficial
    }

    public function testItExpiresGameTopAchieversCache(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create(['User' => 'DevCompliance']);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        // ... pre-populate the cache to verify it gets cleared ...
        $cacheKey = "game:{$game->id}:top-achievers:v3";
        Cache::put($cacheKey, ['test' => 'data'], 60);

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'achievement-demotion',
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertOk();
        $this->assertNull(Cache::get($cacheKey)); // !! cleared
    }

    public function testItRejectsInvalidJsonApiType(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->postJson('/api/internal/achievements/demote', [
            'data' => [
                'type' => 'wrong-type', // !!
                'attributes' => [
                    'achievementId' => $achievement->id,
                    'username' => 'DevCompliance',
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'The selected data.type is invalid.');
    }
}
