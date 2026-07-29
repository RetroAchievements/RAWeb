<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\AccessToken;
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
        $user = User::factory()->create(['web_api_key' => 'test-api-key']);
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
        $user = User::factory()->create(['web_api_key' => 'test-api-key']);
        System::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('Authorization', 'Bearer test-api-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();
    }

    public function testItAuthenticatesViaPassportAccessTokenWithReadScope(): void
    {
        // Arrange
        $user = User::factory()->create();
        System::factory()->create();

        Passport::actingAs($user, ['data:read'], 'oauth');
        $this->assertInstanceOf(AccessToken::class, $user->token());

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

    public function testItRejectsPassportTokenWithoutReadScope(): void
    {
        // Arrange
        $user = User::factory()->create();
        System::factory()->create();

        Passport::actingAs($user, [], 'oauth');
        $this->assertInstanceOf(AccessToken::class, $user->token());

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->get('/api/v2/systems');

        // Assert
        $response->assertForbidden();
    }

    public function testItRejectsPassportTokenWithoutTheGranularScopeForCallerPrivateReads(): void
    {
        // Arrange
        $user = User::factory()->create();

        Passport::actingAs($user, ['data:read'], 'oauth');

        // Act
        $followersResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/followers");
        $followingResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/following");
        $gameListResponse = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->get("/api/v2/users/{$user->ulid}/user-game-list-entries");

        // Assert
        $followersResponse->assertForbidden();
        $followingResponse->assertForbidden();
        $gameListResponse->assertForbidden();

        $this->assertEquals('missing_scope', $followersResponse->json('errors.0.code'));
        $this->assertEquals('follows:read', $followersResponse->json('errors.0.meta.required_scope'));
        $this->assertEquals('follows:read', $followingResponse->json('errors.0.meta.required_scope'));
        $this->assertEquals('game-lists:read', $gameListResponse->json('errors.0.meta.required_scope'));
    }

    public function testItAllowsPassportTokenCarryingTheGranularScopeForCallerPrivateReads(): void
    {
        // Arrange
        $user = User::factory()->create();

        Passport::actingAs($user, ['data:read', 'follows:read', 'game-lists:read'], 'oauth');

        // Act
        $followersResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/followers");
        $followingResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/following");
        $gameListResponse = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->get("/api/v2/users/{$user->ulid}/user-game-list-entries");

        // Assert
        $followersResponse->assertSuccessful();
        $followingResponse->assertSuccessful();
        $gameListResponse->assertSuccessful();
    }

    public function testItRejectsApiKeyForCallerPrivateReads(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-api-key']);

        // Act
        $followersResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-api-key')
            ->get("/api/v2/users/{$user->ulid}/followers");
        $gameListResponse = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-api-key')
            ->get("/api/v2/users/{$user->ulid}/user-game-list-entries");

        // Assert
        $followersResponse->assertForbidden();
        $gameListResponse->assertForbidden();

        $this->assertEquals('oauth_required', $followersResponse->json('errors.0.code'));
        $this->assertEquals('oauth_required', $gameListResponse->json('errors.0.code'));
    }

    public function testItRejectsUnauthenticatedCallerPrivateReads(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/followers");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItKeepsTheTwoGranularScopesIndependent(): void
    {
        // Arrange
        $user = User::factory()->create();

        Passport::actingAs($user, ['data:read', 'follows:read'], 'oauth');

        // Act
        $gameListResponse = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->get("/api/v2/users/{$user->ulid}/user-game-list-entries");

        // Assert
        $gameListResponse->assertForbidden();
        $this->assertEquals('game-lists:read', $gameListResponse->json('errors.0.meta.required_scope'));
    }

    public function testAGranularScopeIsSufficientOnItsOwnWithoutTheUmbrellaReadScope(): void
    {
        // Arrange
        $user = User::factory()->create();

        Passport::actingAs($user, ['follows:read'], 'oauth');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/followers");

        // Assert
        $response->assertSuccessful();
    }

    public function testTheUmbrellaReadScopeStillGatesRoutesWithoutTheirOwnScope(): void
    {
        // Arrange
        $user = User::factory()->create();
        System::factory()->create();

        Passport::actingAs($user, ['follows:read'], 'oauth');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->get('/api/v2/systems');

        // Assert
        $response->assertForbidden();
        $this->assertEquals('data:read', $response->json('errors.0.meta.required_scope'));
    }

    public function testItLeavesPublicRelationshipsReachableByBothCredentialTypes(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-api-key']);

        // Act
        $apiKeyResponse = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-api-key')
            ->get("/api/v2/users/{$user->ulid}/awards");

        Passport::actingAs($user, ['data:read'], 'oauth');
        $oauthResponse = $this->jsonApi('v2')
            ->expects('user-awards')
            ->get("/api/v2/users/{$user->ulid}/awards");

        // Assert
        $apiKeyResponse->assertSuccessful();
        $oauthResponse->assertSuccessful();
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
        $user = User::factory()->create(['web_api_key' => 'test-api-key']);
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

        Passport::actingAs($user, ['data:read'], 'oauth');

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
