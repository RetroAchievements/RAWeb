<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

abstract class JsonApiResourceTestCase extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    /**
     * Get the JSON:API resource type.
     *
     * @example "systems"
     * @example "games"
     */
    abstract protected function resourceType(): string;

    /**
     * Get the resource endpoint path.
     *
     * @example "/api/v2/systems"
     * @example "/api/v2/games"
     */
    abstract protected function resourceEndpoint(): string;

    /**
     * Create a resource instance for testing.
     */
    abstract protected function createResource(): Model;

    /**
     * Get the identifier to use in API URLs for the given resource.
     * Override this for resources that use non-standard identifiers (eg: ULID).
     */
    protected function getResourceIdentifier(Model $resource): string
    {
        return (string) $resource->getKey();
    }

    /**
     * Get the expected JSON:API `id` field value for the given resource.
     * Override this for resources that use non-standard identifiers (eg: ULID).
     */
    protected function getExpectedResourceId(Model $resource): string
    {
        return (string) $resource->getKey();
    }

    /**
     * The common tests below will run for every resource to
     * ensure consistency with auth and JSON:API query params.
     *
     * Note: Index-specific tests (pagination, page size, sorting) are in the
     * TestsJsonApiIndex trait. Resources with index support should use that trait.
     */
    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $resource = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->get("{$this->resourceEndpoint()}/{$this->getResourceIdentifier($resource)}");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItLogsApiRequest(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $resource = $this->createResource();

        $this->assertDatabaseCount('api_logs', 0);

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get("{$this->resourceEndpoint()}/{$this->getResourceIdentifier($resource)}");

        // Assert
        $response->assertSuccessful();
        $this->assertDatabaseCount('api_logs', 1);
        $this->assertDatabaseHas('api_logs', [
            'api_version' => 'v2',
            'user_id' => $user->id,
        ]);
    }

    public function testItFetchesSingleResource(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $resource = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects($this->resourceType())
            ->withHeader('X-API-Key', 'test-key')
            ->get("{$this->resourceEndpoint()}/{$this->getResourceIdentifier($resource)}");

        // Assert
        $response->assertFetchedOne([
            'type' => $this->resourceType(),
            'id' => $this->getExpectedResourceId($resource),
        ]);
    }
}
