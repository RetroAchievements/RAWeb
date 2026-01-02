<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Concerns;

use App\Models\User;

/**
 * Shared tests for JSON:API resources that support the index (list) endpoint.
 * Only use this trait in test classes for resources that have index enabled.
 */
trait TestsJsonApiIndex
{
    public function testItRejectsPageSizeTooLarge(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint() . '?page[size]=1000'); // too large

        // Assert
        $response->assertStatus(400);
    }

    public function testItAcceptsPageSize100(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint() . '?page[size]=100');

        // Assert
        $response->assertSuccessful();
    }

    public function testItRejectsInvalidPageNumber(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint() . '?page[number]=0'); // must be >= 1

        // Assert
        $response->assertStatus(400);
    }

    public function testItRejectsInvalidSortField(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint() . '?sort=invalid_field123');

        // Assert
        $response->assertStatus(400);
    }

    public function testItSupportsPagination(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);

        // ... create enough resources to trigger pagination ...
        for ($i = 0; $i < 30; $i++) {
            $this->createResource();
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint() . '?page[number]=1&page[size]=10');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(10, $response->json('data')); // 10 per page
    }
}
