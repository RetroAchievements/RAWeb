<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Internal\Controllers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsUnauthorizedWhenNoApiKeyIsProvided(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => 'DevCompliance',
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
        $this->seed(RolesTableSeeder::class);

        User::factory()->create(['web_api_key' => 'regular-user-api-key']);
        $achievement = Achievement::factory()->create();

        $demotingUser = User::factory()->create(['username' => 'DevCompliance']);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        // ... this user is not in the allowed service accounts list ...
        config(['api.internal.allowed_user_ids' => '99999']);

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => 'DevCompliance',
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
        $this->seed(RolesTableSeeder::class);

        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);

        // ... this is an actual service account ...
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create([
            'username' => 'DevCompliance',
            'display_name' => 'DevCompliance',
        ]);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => $demotingUser->username,
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
                    'title',
                    'description',
                    'points',
                    'published',
                    'gameId',
                ],
                'meta' => [
                    'updatedAt',
                    'updatedBy',
                    'updatedFields',
                ],
            ],
        ]);
        $response->assertJsonPath('data.type', 'achievements');
        $response->assertJsonPath('data.id', (string) $achievement->id);
        $response->assertJsonPath('data.attributes.published', false);
        $response->assertJsonPath('data.meta.updatedBy', 'DevCompliance');
        $response->assertJsonPath('data.meta.updatedFields', ['published']);

        $achievement->refresh();
        $this->assertEquals(AchievementFlag::Unofficial->value, $achievement->Flags); // !! demoted
    }

    public function testItSuccessfullyDemotesAnAchievementWithTitleChange(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create([
            'username' => 'DevCompliance',
            'display_name' => 'DevCompliance',
        ]);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Title' => 'Original Title',
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                    'title' => 'DEMOTED AS UNWELCOME CONCEPT - Original Title', // !! new title
                ],
                'meta' => [
                    'actingUser' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.attributes.title', 'DEMOTED AS UNWELCOME CONCEPT - Original Title');
        $response->assertJsonPath('data.meta.updatedFields', ['published', 'title']);

        $achievement->refresh();
        $this->assertEquals(AchievementFlag::Unofficial->value, $achievement->Flags);
        $this->assertEquals('DEMOTED AS UNWELCOME CONCEPT - Original Title', $achievement->title); // !!
    }

    public function testItReturnsValidationErrorForMissingId(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        $demotingUser = User::factory()->create(['username' => 'DevCompliance']);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                // ... no id ...
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => 'DevCompliance',
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
        $response->assertJsonPath('errors.0.detail', 'The achievement ID is required.');
    }

    public function testItReturnsValidationErrorForMissingActingUser(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    // ... no actingUser ...
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'The data.meta field is required.');
    }

    public function testItReturnsErrorForNonExistentAchievement(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create(['username' => 'DevCompliance']);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        // Act
        $response = $this->patchJson('/api/internal/achievements/999999', [ // !!
            'data' => [
                'type' => 'achievements',
                'id' => 999999,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertNotFound();
        $response->assertJsonStructure([
            'message',
            'errors' => [
                [
                    'status',
                    'code',
                    'title',
                    'detail',
                ],
            ],
        ]);
        $response->assertJsonPath('errors.0.code', 'not_found');
        $response->assertJsonPath('errors.0.detail', 'No achievement found with ID 999999.');
    }

    public function testItReturnsErrorForNonExistentUsername(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => 'NonExistentUser', // !!
                    'action' => 'demote',
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

    public function testItReturnsErrorWhenDemotingAlreadyDemotedAchievement(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $demotingUser = User::factory()->create([
            'username' => 'DevCompliance',
            'display_name' => 'DevCompliance',
        ]);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        $game = Game::factory()->create();
        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'Flags' => AchievementFlag::Unofficial->value, // !! already unofficial
        ]);

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'achievements',
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => $demotingUser->username,
                ],
            ],
        ], [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.code', 'no_changes');
        $response->assertJsonPath('errors.0.detail', 'No changes to apply.');

        $achievement->refresh();
        $this->assertEquals(AchievementFlag::Unofficial->value, $achievement->Flags); // still unofficial
    }

    public function testItRejectsInvalidJsonApiType(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $serviceAccount = User::factory()->create([
            'username' => 'RABot',
            'web_api_key' => 'rabot-api-key',
        ]);
        config(['api.internal.allowed_user_ids' => (string) $serviceAccount->id]);

        $achievement = Achievement::factory()->create();

        $demotingUser = User::factory()->create(['username' => 'DevCompliance']);
        $demotingUser->assignRole(Role::TEAM_ACCOUNT);

        // Act
        $response = $this->patchJson('/api/internal/achievements/' . $achievement->id, [
            'data' => [
                'type' => 'wrong-type', // !!
                'id' => $achievement->id,
                'attributes' => [
                    'published' => false,
                ],
                'meta' => [
                    'actingUser' => 'DevCompliance',
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
