<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class MultiAuthTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItAuthenticatesViaXApiKeyHeader(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-api-key']);
        System::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-api-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();
    }

    public function testItAuthenticatesViaBearerTokenAsApiKey(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-api-key']);
        System::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('Authorization', 'Bearer test-api-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();
    }

    public function testItAuthenticatesViaPassportBearerToken(): void
    {
        // Arrange
        $user = User::factory()->create();
        System::factory()->create();

        Passport::actingAs($user, [], 'oauth');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();
    }

    public function testItRejectsInvalidBearerToken(): void
    {
        // Arrange
        System::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('Authorization', 'Bearer invalid-token-that-is-neither-api-key-nor-passport')
            ->get('/api/v2/systems');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItRejectsRequestWithNoAuthentication(): void
    {
        // Arrange
        System::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->get('/api/v2/systems');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItSetsCorrectUserOnRequestWithApiKey(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-api-key']);
        System::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-api-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $user->id,
            'api_version' => 'v2',
        ]);
    }

    public function testItSetsCorrectUserOnRequestWithPassportToken(): void
    {
        // Arrange
        $user = User::factory()->create();
        System::factory()->create();

        Passport::actingAs($user, [], 'oauth');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $user->id,
            'api_version' => 'v2',
        ]);
    }
}
