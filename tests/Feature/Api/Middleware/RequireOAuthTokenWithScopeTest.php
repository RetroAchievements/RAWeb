<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class RequireOAuthTokenWithScopeTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItAllowsAnOAuthTokenCarryingTheRequiredScope(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user, ['data:read', 'follows:read'], 'oauth');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/followers");

        // Assert
        $response->assertSuccessful();
    }

    public function testItRejectsAnOAuthTokenMissingTheRequiredScope(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user, ['data:read'], 'oauth');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$user->ulid}/followers");

        // Assert
        $response->assertForbidden();
        $this->assertEquals('missing_scope', $response->json('errors.0.code'));
        $this->assertEquals('follows:read', $response->json('errors.0.meta.required_scope'));
    }

    public function testItRejectsAnApiKeyOutsideLocal(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-api-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-api-key')
            ->get("/api/v2/users/{$user->ulid}/followers");

        // Assert
        $response->assertForbidden();
        $this->assertEquals('oauth_required', $response->json('errors.0.code'));
        $this->assertEquals('follows:read', $response->json('errors.0.meta.required_scope'));
    }
}
