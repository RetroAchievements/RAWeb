<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Controllers;

use App\Models\ApiLogEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsUnauthorizedWhenNoApiKeyProvided(): void
    {
        // Act
        $response = $this->getJson('/api/v2/health');

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function testItReturnsOkWhenAuthenticated(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-api-key']);

        // Act
        $response = $this->getJson('/api/v2/health', [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function testItReturnsCorrectJsonStructure(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-api-key']);

        // Act
        $response = $this->getJson('/api/v2/health', [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);
        $response->assertJson(['status' => 'ok']);

        $timestamp = $response->json('timestamp');
        $this->assertNotNull($timestamp);
        $this->assertIsString($timestamp);
    }

    public function testItLogsApiRequest(): void
    {
        // Arrange
        $user = User::factory()->create(['APIKey' => 'test-api-key']);

        $this->assertDatabaseCount('api_logs', 0); // no logs before request

        // Act
        $response = $this->getJson('/api/v2/health', [
            'X-API-Key' => 'test-api-key',
        ]);

        // Assert
        $response->assertOk();

        $this->assertDatabaseCount('api_logs', 1); // logged

        $logEntry = ApiLogEntry::first();
        $this->assertEquals('v2', $logEntry->api_version); // !! logged as V2 API
        $this->assertEquals($user->id, $logEntry->user_id);
        $this->assertEquals('api/v2/health', $logEntry->endpoint);
        $this->assertEquals('GET', $logEntry->method);
        $this->assertEquals(200, $logEntry->response_code);
        $this->assertGreaterThanOrEqual(0, $logEntry->response_time_ms);
    }
}
