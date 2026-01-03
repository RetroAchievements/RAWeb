<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class SystemsTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'systems';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/systems';
    }

    protected function createResource(): Model
    {
        return System::factory()->create();
    }

    public function testItListsSystems(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create([
            'name' => 'PlayStation',
            'active' => true,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'systems', 'id' => (string) $system->id],
        ]);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->count(100)->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));

        $this->assertGreaterThan(50, $response->json('meta.page.total')); // more than one page exists
        $this->assertArrayHasKey('next', $response->json('links')); // has next page link
    }

    public function testItFiltersByActive(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $active = System::factory()->create(['active' => true]); // active system
        $inactive = System::factory()->create(['active' => false]); // inactive system

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/systems?filter[active]=true');

        // Assert
        $response->assertSuccessful();
        $activeIds = collect($response->json('data'))->pluck('attributes.active')->toArray();
        $this->assertTrue(in_array(true, $activeIds)); // at least one active
        $this->assertFalse(in_array(false, $activeIds)); // no inactive
    }

    public function testItExcludesNonGameSystems(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $gameSystem = System::factory()->create();
        $hubsSystem = System::factory()->create(['id' => System::Hubs]);
        $eventsSystem = System::factory()->create(['id' => System::Events]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/systems');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains((string) System::Hubs, $ids); // hubs excluded
        $this->assertNotContains((string) System::Events, $ids); // events excluded
    }

    public function testItSortsByName(): void
    {
        // Arrange
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['name' => 'Zulu System']);
        System::factory()->create(['name' => 'Alpha System']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('systems')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/systems?sort=name');

        // Assert
        $response->assertSuccessful();

        // ... first name should be alphabetically before the second ...
        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertLessThan(0, strcmp($names[0], $names[1]));
    }
}
