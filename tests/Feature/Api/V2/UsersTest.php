<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Game;
use App\Models\PlayerGlobalRanking;
use App\Models\PlayerGlobalRankingTotal;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;

class UsersTest extends JsonApiResourceTestCase
{
    use TestsJsonApiIndex;

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
        $apiUser = User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);
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

    public function testItSortsByPointsByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        User::factory()->create(['points_hardcore' => 100]);
        User::factory()->create(['points_hardcore' => 500]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertSuccessful();
        $points = collect($response->json('data'))->pluck('attributes.pointsHardcore')->toArray();
        $this->assertGreaterThanOrEqual($points[1], $points[0]);
    }

    public function testItSortsByPointsWeighted(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        User::factory()->create(['points_weighted' => 100]);
        User::factory()->create(['points_weighted' => 500]);

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
        User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);

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
        User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create(['username' => 'LegacyUsername', 'display_name' => 'DifferentDisplayName']);

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
        User::factory()->create(['web_api_key' => 'test-key']);

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
        User::factory()->create(['web_api_key' => 'test-key']);
        User::factory()->create(['display_name' => 'BannedUser', 'banned_at' => now()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/BannedUser');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404ForUnverifiedUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        User::factory()->create(['display_name' => 'UnverifiedUser', 'email_verified_at' => null]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/UnverifiedUser');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create([
            'display_name' => 'TestPlayer',
            'username' => 'TestPlayer',
            'motto' => 'Test motto',
            'points_hardcore' => 5000,
            'points' => 100,
            'points_weighted' => 15000,
            'yield_unlocks' => 50,
            'yield_points' => 1000,
            'is_user_wall_active' => true,
            'rich_presence' => 'Playing a game',
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
        $this->assertEquals('Test motto', $attributes['motto']);
        $this->assertEquals(100, $attributes['points']);
        $this->assertEquals(5000, $attributes['pointsHardcore']);
        $this->assertEquals(15000, $attributes['pointsWeighted']);
        $this->assertEquals(50, $attributes['yieldUnlocks']);
        $this->assertEquals(1000, $attributes['yieldPoints']);
        $this->assertTrue($attributes['isUserWallActive']);
        $this->assertFalse($attributes['isUnranked']);
        $this->assertEquals('Playing a game', $attributes['richPresence']);
        $this->assertArrayHasKey('avatarUrl', $attributes);
        $this->assertArrayHasKey('joinedAt', $attributes);
        $this->assertArrayHasKey('lastActivityAt', $attributes);
        $this->assertArrayHasKey('richPresenceUpdatedAt', $attributes);
        $this->assertArrayHasKey('visibleRole', $attributes);
        $this->assertArrayHasKey('displayableRoles', $attributes);
        $this->assertArrayNotHasKey('deletedAt', $attributes);
    }

    public function testItSupportsSparseUserFieldsWithoutChangingTheFullUserShape(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create(['display_name' => 'SparseFieldsUser']);
        $role = Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
        $user->assignRole($role);

        // Act
        $sparseResponse = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}?fields[users]=displayName,avatarUrl");
        $fullResponse = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $sparseResponse->assertSuccessful();
        $sparseAttributes = $sparseResponse->json('data.attributes');
        $this->assertSame(['displayName', 'avatarUrl'], array_keys($sparseAttributes));
        $this->assertSame('SparseFieldsUser', $sparseAttributes['displayName']);

        $fullResponse->assertSuccessful();
        $this->assertSame(Role::DEVELOPER, $fullResponse->json('data.attributes.visibleRole'));
        $this->assertSame([Role::DEVELOPER], $fullResponse->json('data.attributes.displayableRoles'));
    }

    public function testItComputesVisibleRoleWhenRequestedWithoutIncludingDisplayableRoles(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create(['display_name' => 'VisibleRoleUser']);
        $role = Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
        $user->assignRole($role);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}?fields[users]=displayName,visibleRole");

        // Assert
        $response->assertSuccessful();
        $this->assertSame(Role::DEVELOPER, $response->json('data.attributes.visibleRole'));
        $this->assertArrayNotHasKey('displayableRoles', $response->json('data.attributes'));
    }

    public function testItDoesNotTrimUserFieldsForOtherResourceFieldsets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();
        $role = Role::create(['name' => Role::DEVELOPER, 'display' => 1]);
        $user->assignRole($role);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}?fields[achievements]=title");

        // Assert
        $response->assertSuccessful();
        $this->assertSame(Role::DEVELOPER, $response->json('data.attributes.visibleRole'));
        $this->assertSame([Role::DEVELOPER], $response->json('data.attributes.displayableRoles'));
    }

    public function testItReturnsMaterializedRanksAndRankedUserMeta(): void
    {
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();

        PlayerGlobalRanking::factory()->create([
            'user_id' => $user->id,
            'window' => GlobalRankingWindow::AllTime,
            'mode' => GlobalRankingMode::Hardcore,
            'rank_number' => 2,
        ]);
        PlayerGlobalRanking::factory()->create([
            'user_id' => $user->id,
            'window' => GlobalRankingWindow::AllTime,
            'mode' => GlobalRankingMode::Casual,
            'rank_number' => 3,
        ]);
        PlayerGlobalRankingTotal::query()->insert([
            ['rank_type' => 1, 'total' => 20, 'created_at' => now()],
            ['rank_type' => 2, 'total' => 30, 'created_at' => now()],
        ]);

        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        $response->assertSuccessful();
        $this->assertSame(2, $response->json('data.attributes.rankHardcore'));
        $this->assertSame(3, $response->json('data.attributes.rankCasual'));
        $this->assertSame(20, $response->json('meta.rankedUsers.hardcore'));
        $this->assertSame(30, $response->json('meta.rankedUsers.casual'));
        $this->assertArrayNotHasKey('rankedUsersHardcore', $response->json('data.attributes'));
        $this->assertArrayNotHasKey('rankedUsersCasual', $response->json('data.attributes'));
    }

    public function testItReturnsRankedUserCountsAsResponseMetaForUserCollections(): void
    {
        $apiUser = User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();
        PlayerGlobalRankingTotal::query()->insert([
            ['rank_type' => 1, 'total' => 20, 'created_at' => now()],
            ['rank_type' => 2, 'total' => 30, 'created_at' => now()],
        ]);

        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        $response->assertSuccessful();
        $this->assertSame(20, $response->json('meta.rankedUsers.hardcore'));
        $this->assertSame(30, $response->json('meta.rankedUsers.casual'));
        $this->assertArrayHasKey('page', $response->json('meta'));

        foreach ($response->json('data') as $resource) {
            $this->assertArrayNotHasKey('rankedUsersHardcore', $resource['attributes']);
            $this->assertArrayNotHasKey('rankedUsersCasual', $resource['attributes']);
        }
    }

    public function testItReturnsNullMaterializedRanksForUnrankedUsers(): void
    {
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->untracked()->create();
        PlayerGlobalRanking::factory()->create([
            'user_id' => $user->id,
            'window' => GlobalRankingWindow::AllTime,
            'mode' => GlobalRankingMode::Hardcore,
            'rank_number' => 1,
        ]);

        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        $response->assertSuccessful();
        $this->assertNull($response->json('data.attributes.rankHardcore'));
        $this->assertNull($response->json('data.attributes.rankCasual'));
    }

    public function testItOmitsMaterializedRanksWhenTheSparseFieldsetExcludesThem(): void
    {
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();

        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}?fields[users]=displayName");

        $response->assertSuccessful();
        $this->assertArrayNotHasKey('rankHardcore', $response->json('data.attributes'));
        $this->assertArrayNotHasKey('rankCasual', $response->json('data.attributes'));
        $this->assertArrayNotHasKey('rankedUsersHardcore', $response->json('data.attributes'));
        $this->assertArrayNotHasKey('rankedUsersCasual', $response->json('data.attributes'));
        $this->assertSame(0, $response->json('meta.rankedUsers.hardcore'));
        $this->assertSame(0, $response->json('meta.rankedUsers.casual'));
    }

    public function testItCanIncludeLastGameRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['title' => 'Super Mario Bros.']);
        $user = User::factory()->create();
        $user->rich_presence_game_id = $game->id;
        $user->save();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}?include=lastGame");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('games', $response->json('data.relationships.lastGame.data.type'));
        $this->assertEquals((string) $game->id, $response->json('data.relationships.lastGame.data.id'));

        $included = collect($response->json('included'));
        $includedGame = $included->firstWhere('type', 'games');
        $this->assertNotNull($includedGame);
        $this->assertEquals((string) $game->id, $includedGame['id']);
        $this->assertEquals('Super Mario Bros.', $includedGame['attributes']['title']);
    }

    public function testItCanIncludeLastGameRelationshipOnIndex(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['title' => 'Super Mario Bros.']);
        $user = User::factory()->create();
        $user->rich_presence_game_id = $game->id;
        $user->save();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?include=lastGame');

        // Assert
        $response->assertSuccessful();
        $indexedUser = collect($response->json('data'))->firstWhere('id', $user->ulid);
        $this->assertNotNull($indexedUser);
        $this->assertEquals((string) $game->id, $indexedUser['relationships']['lastGame']['data']['id']);

        $included = collect($response->json('included'));
        $includedGame = $included->firstWhere('type', 'games');
        $this->assertNotNull($includedGame);
        $this->assertEquals((string) $game->id, $includedGame['id']);
    }

    public function testItReturnsNullLastGameRelationshipWhenUserHasNeverPlayed(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();
        $user->rich_presence_game_id = 0;
        $user->save();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}?include=lastGame");

        // Assert
        $response->assertSuccessful();
        $this->assertNull($response->json('data.relationships.lastGame.data'));
        $this->assertEmpty($response->json('included'));
    }

    public function testItDoesNotIncludeLastGameDataByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create();
        $user = User::factory()->create();
        $user->rich_presence_game_id = $game->id;
        $user->save();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$user->ulid}");

        // Assert
        $response->assertSuccessful();
        $this->assertNull($response->json('data.relationships'));
    }

    public function testItIncludesWebUrlLink(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
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
        $this->assertArrayHasKey('webUrl', $links);
        $this->assertStringContainsString('/user/LinkTestUser', $links['webUrl']);
    }

    public function testItReturnsVisibleRole(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
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
        User::factory()->create(['web_api_key' => 'test-key']);
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

    public function testItFiltersByDisplayableRole(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $developer = User::factory()->create();
        $artist = User::factory()->create();

        $developerRole = Role::create(['name' => 'developer', 'display' => 1]);
        $artistRole = Role::create(['name' => 'artist', 'display' => 2]);
        $developer->assignRole($developerRole);
        $artist->assignRole($artistRole);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?filter[role]=developer');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($developer->ulid, $ids);
        $this->assertNotContains($artist->ulid, $ids);
    }

    public function testItDoesNotExposeHiddenRolesViaFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();

        $hiddenRole = Role::create([
            'name' => 'root',
            'display' => 0, // !!
        ]);
        $user->assignRole($hiddenRole);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?filter[role]=root');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($user->ulid, $ids);
    }

    public function testItSupportsTheTopTenUsersRecipe(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key', 'points_hardcore' => 0]);

        // ... an unranked user with the highest score must not appear ...
        $unrankedUser = User::factory()->untracked()->create(['points_hardcore' => 99999]);

        foreach ([1100, 1000, 900, 700, 600, 500, 400, 300, 200] as $points) {
            User::factory()->create(['points_hardcore' => $points]);
        }

        // ... two users tied on hardcore points break the tie by weighted points ...
        $tieWinner = User::factory()->create(['points_hardcore' => 800, 'points_weighted' => 5000]);
        $tieLoser = User::factory()->create(['points_hardcore' => 800, 'points_weighted' => 3000]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?sort=-pointsHardcore,-pointsWeighted&filter[ranked]=true&page[size]=10');

        // Assert
        $response->assertSuccessful();

        $data = collect($response->json('data'));
        $this->assertCount(10, $data);

        $points = $data->pluck('attributes.pointsHardcore')->toArray();
        $this->assertEquals([1100, 1000, 900, 800, 800, 700, 600, 500, 400, 300], $points);

        $ids = $data->pluck('id')->toArray();
        $this->assertEquals($tieWinner->ulid, $ids[3]);
        $this->assertEquals($tieLoser->ulid, $ids[4]);
        $this->assertNotContains($unrankedUser->ulid, $ids);
    }

    public function testItFiltersToOnlyUnrankedUsers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $rankedUser = User::factory()->create();
        $unrankedUser = User::factory()->untracked()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?filter[ranked]=false');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($unrankedUser->ulid, $ids);
        $this->assertNotContains($rankedUser->ulid, $ids);
    }

    public function testItRejectsAnInvalidRankedFilterValue(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users?filter[ranked]=banana');

        // Assert
        $response->assertStatus(400);
    }

    public function testItDoesNotExcludeUnrankedUsersByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $rankedUser = User::factory()->create();
        $unrankedUser = User::factory()->untracked()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('users')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($rankedUser->ulid, $ids);
        $this->assertContains($unrankedUser->ulid, $ids);
    }

    public function testItReturnsUnrankedStatus(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
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
