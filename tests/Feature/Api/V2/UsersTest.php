<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UsersTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'users';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/users';
    }

    protected function createResource(): Model
    {
        return User::factory()->create();
    }

    /**
     * Users use ULID as the identifier in API URLs.
     */
    protected function getResourceIdentifier(Model $resource): string
    {
        return $resource->ulid;
    }

    /**
     * Users return ULID as the JSON:API `id` field (not a numeric ID).
     */
    protected function getExpectedResourceId(Model $resource): string
    {
        return $resource->ulid;
    }

    public function testItListsUsers(): void
    {
        // Arrange
        $apiUser = User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create(['display_name' => 'TestUser']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'users', 'id' => $apiUser->ulid],
            ['type' => 'users', 'id' => $user->ulid],
        ]);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        User::factory()->count(60)->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));

        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('page', $response->json('meta'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
    }

    public function testItExcludesBannedUsers(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $normalUser = User::factory()->create();
        $bannedUser = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($normalUser->ulid, $ids);
        $this->assertNotContains($bannedUser->ulid, $ids);
    }

    public function testItExcludesUnverifiedUsers(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $verifiedUser = User::factory()->create();
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($verifiedUser->ulid, $ids);
        $this->assertNotContains($unverifiedUser->ulid, $ids);
    }

    public function testItFiltersByDisplayName(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user1 = User::factory()->create(['display_name' => 'AlphaUser']);
        $user2 = User::factory()->create(['display_name' => 'BetaUser']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?filter[displayName]=AlphaUser');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($user1->ulid, $ids);
        $this->assertNotContains($user2->ulid, $ids);
    }

    public function testItFiltersByUsername(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user1 = User::factory()->create(['User' => 'LegacyName1', 'display_name' => 'CurrentName1']);
        $user2 = User::factory()->create(['User' => 'LegacyName2', 'display_name' => 'CurrentName2']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?filter[username]=LegacyName1');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($user1->ulid, $ids);
        $this->assertNotContains($user2->ulid, $ids);
    }

    public function testItSortsByPointsByDefault(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        User::factory()->create(['RAPoints' => 100]);
        User::factory()->create(['RAPoints' => 500]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertSuccessful();
        $points = collect($response->json('data'))->pluck('attributes.points')->toArray();
        $this->assertGreaterThanOrEqual($points[1], $points[0]);
    }

    public function testItSortsByPointsWeighted(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        User::factory()->create(['TrueRAPoints' => 100]);
        User::factory()->create(['TrueRAPoints' => 500]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?sort=-pointsWeighted');

        // Assert
        $response->assertSuccessful();
        $points = collect($response->json('data'))->pluck('attributes.pointsWeighted')->toArray();
        $this->assertGreaterThanOrEqual($points[1], $points[0]);
    }

    public function testItReturnsUserByUlid(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create(['display_name' => 'TestUser']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertFetchedOne([
            'type' => 'users',
            'id' => $user->ulid,
        ]);
    }

    public function testItReturnsUlidAsIdField(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals($user->ulid, $response->json('data.id'));
        $this->assertEquals(26, strlen($response->json('data.id'))); // ULIDs are 26 chars
    }

    public function testItReturns404ForInvalidUlid(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/01INVALIDULIDXXXXXXXX12345');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturnsUserByDisplayName(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create(['display_name' => 'UniqueTestUser']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/UniqueTestUser');

        // Assert
        $response->assertFetchedOne([
            'type' => 'users',
            'id' => $user->ulid,
        ]);
    }

    public function testItReturnsUserByUsername(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create(['User' => 'LegacyUsername', 'display_name' => 'DifferentDisplayName']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/LegacyUsername');

        // Assert
        $response->assertFetchedOne([
            'type' => 'users',
            'id' => $user->ulid,
        ]);
    }

    public function testItReturns404ForNonExistentUser(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/NonExistentUser');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404ForBannedUser(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        User::factory()->create(['display_name' => 'BannedUser', 'banned_at' => now()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/BannedUser');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create([
            'display_name' => 'TestPlayer',
            'User' => 'TestPlayer',
            'Motto' => 'Test motto',
            'RAPoints' => 5000,
            'RASoftcorePoints' => 100,
            'TrueRAPoints' => 15000,
            'ContribCount' => 50,
            'ContribYield' => 1000,
            'UserWallActive' => true,
            'RichPresenceMsg' => 'Playing a game',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('TestPlayer', $attributes['displayName']);
        $this->assertEquals('TestPlayer', $attributes['username']);
        $this->assertEquals('Test motto', $attributes['motto']);
        $this->assertEquals(5000, $attributes['points']);
        $this->assertEquals(100, $attributes['pointsSoftcore']);
        $this->assertEquals(15000, $attributes['pointsWeighted']);
        $this->assertEquals(50, $attributes['yieldUnlocks']);
        $this->assertEquals(1000, $attributes['yieldPoints']);
        $this->assertTrue($attributes['isUserWallActive']);
        $this->assertFalse($attributes['isUnranked']);
        $this->assertEquals('Playing a game', $attributes['richPresenceMessage']);
        $this->assertArrayHasKey('avatarUrl', $attributes);
        $this->assertArrayHasKey('joinedAt', $attributes);
        $this->assertArrayHasKey('lastActivityAt', $attributes);
        $this->assertArrayHasKey('richPresenceUpdatedAt', $attributes);
        $this->assertArrayHasKey('visibleRole', $attributes);
        $this->assertArrayHasKey('displayableRoles', $attributes);
    }

    public function testItIncludesProfileLink(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create(['display_name' => 'LinkTestUser']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $links = $response->json('data.links');

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('profile', $links);
        $this->assertStringContainsString('/user/LinkTestUser', $links['profile']);
    }

    public function testItReturnsVisibleRole(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create();

        $role = Role::create(['name' => 'developer', 'display' => 1]);
        $user->assignRole($role);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('developer', $response->json('data.attributes.visibleRole'));
    }

    public function testItReturnsDisplayableRoles(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create();

        $role1 = Role::create(['name' => 'developer', 'display' => 1]);
        $role2 = Role::create(['name' => 'artist', 'display' => 2]);
        $user->assignRole($role1);
        $user->assignRole($role2);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $displayableRoles = $response->json('data.attributes.displayableRoles');
        $this->assertIsArray($displayableRoles);
        $this->assertContains('developer', $displayableRoles);
        $this->assertContains('artist', $displayableRoles);
    }

    public function testItReturnsUnrankedStatus(): void
    {
        // Arrange
        User::factory()->create(['APIKey' => 'test-key']);
        $user = User::factory()->create(['unranked_at' => now()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $this->assertTrue($response->json('data.attributes.isUnranked'));
    }
}
