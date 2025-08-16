<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Internal\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsUnauthorizedWhenNoApiKeyProvided(): void
    {
        // Act
        $response = $this->getJson('/api/internal/health');

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function testItReturnsForbiddenWhenUserIsNotServiceAccount(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'regular-user-api-key']);

        // ... this user is not in the allowed service accounts list ...
        config(['internal-api.allowed_user_ids' => '99999']);

        // Act
        $response = $this->getJson('/api/internal/health', [
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

    public function testItReturnsOkWhenServiceAccountAuthenticated(): void
    {
        // Arrange
        $serviceAccount = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);

        // ... this is an actual service account ...
        config(['internal-api.allowed_user_ids' => (string) $serviceAccount->id]);

        // Act
        $response = $this->getJson('/api/internal/health', [
            'X-API-Key' => 'rabot-api-key',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);
        $response->assertJson(['status' => 'ok']);
    }

    public function testItAllowsMultipleServiceAccounts(): void
    {
        // Arrange
        $serviceAccount1 = User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);
        $serviceAccount2 = User::factory()->create([
            'User' => 'CronService',
            'APIKey' => 'cron-api-key',
        ]);

        // ... configure multiple service accounts as comma-separated IDs ...
        config(['internal-api.allowed_user_ids' => "{$serviceAccount1->id},{$serviceAccount2->id}"]);

        // Act
        $response1 = $this->getJson('/api/internal/health', [
            'X-API-Key' => 'rabot-api-key',
        ]);
        $response2 = $this->getJson('/api/internal/health', [
            'X-API-Key' => 'cron-api-key',
        ]);

        // Assert
        $response1->assertOk();
        $response2->assertOk();
    }

    public function testItHandlesEmptyAllowedUserIdsConfiguration(): void
    {
        // Arrange
        User::factory()->create([
            'User' => 'RABot',
            'APIKey' => 'rabot-api-key',
        ]);

        // ... no service accounts are configured ...
        config(['internal-api.allowed_user_ids' => '']);

        // Act
        $response = $this->getJson('/api/internal/health', [
            'X-API-Key' => 'rabot-api-key',
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
}
