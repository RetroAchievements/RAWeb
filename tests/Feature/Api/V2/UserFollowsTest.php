<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Enums\UserRelationStatus;
use App\Models\Game;
use App\Models\User;
use App\Models\UserRelation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class UserFollowsTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthenticationOnFollowers(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItRequiresAuthenticationOnFollowing(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItForbidsReadingAnotherUsersFollowers(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $other = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$other->ulid}/followers");

        // Assert
        $response->assertStatus(403);
    }

    public function testItForbidsReadingAnotherUsersFollowing(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $other = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$other->ulid}/following");

        // Assert
        $response->assertStatus(403);
    }

    public function testItReturnsAnEmptyListWhenTheAuthUserHasNoFollows(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $followers = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        $following = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $followers->assertSuccessful();
        $following->assertSuccessful();
        $this->assertEquals([], $followers->json('data'));
        $this->assertEquals([], $following->json('data'));
        $this->assertEquals(0, $followers->json('meta.page.total'));
        $this->assertEquals(0, $following->json('meta.page.total'));
    }

    public function testItListsTheAuthUsersFollowersWithInlineContext(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $follower = User::factory()->create([
            'display_name' => 'FollowerOne',
            'points' => 1234,
            'points_hardcore' => 5678,
        ]);

        UserRelation::create([
            'user_id' => $follower->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        // Assert
        $response->assertSuccessful();

        $attributes = $response->json('data.0.attributes');

        // ... the "other user" is the follower, not the auth user ...
        $this->assertEquals($follower->ulid, $attributes['userId']);
        $this->assertEquals('FollowerOne', $attributes['displayName']);
        $this->assertEquals(1234, $attributes['points']);
        $this->assertEquals(5678, $attributes['pointsHardcore']);
        $this->assertNotNull($attributes['avatarUrl']);
        $this->assertNotNull($attributes['followedAt']);
        $this->assertFalse($attributes['isMutual']);
    }

    public function testItListsTheAuthUsersFollowingWithInlineContext(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $followed = User::factory()->create([
            'display_name' => 'FollowedOne',
            'points' => 100,
            'points_hardcore' => 200,
        ]);

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $followed->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertSuccessful();

        $attributes = $response->json('data.0.attributes');

        $this->assertEquals($followed->ulid, $attributes['userId']);
        $this->assertEquals('FollowedOne', $attributes['displayName']);
        $this->assertEquals(100, $attributes['points']);
        $this->assertEquals(200, $attributes['pointsHardcore']);
        $this->assertFalse($attributes['isMutual']);
    }

    public function testItCanIncludeUserRelationshipOnFollowers(): void
    {
        // Arrange
        $auth = User::factory()->create([
            'display_name' => 'AuthenticatedUser',
            'web_api_key' => 'test-key',
        ]);
        $follower = User::factory()->create([
            'display_name' => 'FollowerWithPresence',
            'last_activity_at' => Carbon::parse('2026-06-01 12:00:00'),
            'rich_presence' => 'Playing Stage 1',
            'rich_presence_updated_at' => Carbon::parse('2026-06-01 12:05:00'),
        ]);

        UserRelation::create([
            'user_id' => $follower->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers?include=user");

        // Assert
        $response->assertSuccessful();

        $this->assertEquals('users', $response->json('data.0.relationships.user.data.type'));
        $this->assertEquals($follower->ulid, $response->json('data.0.relationships.user.data.id'));

        $included = $response->json('included');
        $this->assertCount(1, $included);
        $this->assertEquals('users', $included[0]['type']);
        $this->assertEquals($follower->ulid, $included[0]['id']);
        $this->assertEquals('FollowerWithPresence', $included[0]['attributes']['displayName']);
        $this->assertEquals('Playing Stage 1', $included[0]['attributes']['richPresence']);
        $this->assertNotNull($included[0]['attributes']['lastActivityAt']);
        $this->assertNotNull($included[0]['attributes']['richPresenceUpdatedAt']);
    }

    public function testItCanIncludeUserRelationshipOnFollowing(): void
    {
        // Arrange
        $auth = User::factory()->create([
            'display_name' => 'AuthenticatedUser',
            'web_api_key' => 'test-key',
        ]);
        $followed = User::factory()->create([
            'display_name' => 'FollowedWithPresence',
            'last_activity_at' => Carbon::parse('2026-06-02 12:00:00'),
            'rich_presence' => 'Playing Stage 2',
            'rich_presence_updated_at' => Carbon::parse('2026-06-02 12:05:00'),
        ]);

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $followed->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following?include=user");

        // Assert
        $response->assertSuccessful();

        $this->assertEquals('users', $response->json('data.0.relationships.user.data.type'));
        $this->assertEquals($followed->ulid, $response->json('data.0.relationships.user.data.id'));

        $included = $response->json('included');
        $this->assertCount(1, $included);
        $this->assertEquals('users', $included[0]['type']);
        $this->assertEquals($followed->ulid, $included[0]['id']);
        $this->assertEquals('FollowedWithPresence', $included[0]['attributes']['displayName']);
        $this->assertEquals('Playing Stage 2', $included[0]['attributes']['richPresence']);
        $this->assertNotNull($included[0]['attributes']['lastActivityAt']);
        $this->assertNotNull($included[0]['attributes']['richPresenceUpdatedAt']);
    }

    public function testItCanIncludeLastGameForUsersOnFollowing(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create(['title' => 'Super Mario Bros.']);
        $followed = User::factory()->create(['rich_presence_game_id' => $game->id]);

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $followed->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following?include=user.lastGame");

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));
        $includedUser = $included->firstWhere('type', 'users');
        $includedGame = $included->firstWhere('type', 'games');

        $this->assertNotNull($includedUser);
        $this->assertEquals($followed->ulid, $includedUser['id']);
        $this->assertEquals((string) $game->id, $includedUser['relationships']['lastGame']['data']['id']);

        $this->assertNotNull($includedGame);
        $this->assertEquals((string) $game->id, $includedGame['id']);
        $this->assertEquals('Super Mario Bros.', $includedGame['attributes']['title']);
    }

    public function testIsMutualIsTrueWhenBothFollow(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $other = User::factory()->create();

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $other->id,
            'status' => UserRelationStatus::Following,
        ]);
        UserRelation::create([
            'user_id' => $other->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $followingResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        $followersResponse = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        // Assert
        $followingResponse->assertSuccessful();
        $followersResponse->assertSuccessful();
        $this->assertTrue($followingResponse->json('data.0.attributes.isMutual'));
        $this->assertTrue($followersResponse->json('data.0.attributes.isMutual'));
    }

    public function testIsMutualIsFalseOnFollowingWhenTheOtherUserDoesNotFollowBack(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $notReciprocating = User::factory()->create();

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $notReciprocating->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertSuccessful();
        $this->assertFalse($response->json('data.0.attributes.isMutual'));
    }

    public function testIsMutualIsFalseOnFollowersWhenTheAuthUserDoesNotFollowBack(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $follower = User::factory()->create();

        UserRelation::create([
            'user_id' => $follower->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        // Assert
        $response->assertSuccessful();
        $this->assertFalse($response->json('data.0.attributes.isMutual'));
    }

    public function testItDefaultsToFollowedAtDescendingOnFollowing(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $oldest = User::factory()->create(['display_name' => 'Oldest']);
        $middle = User::factory()->create(['display_name' => 'Middle']);
        $newest = User::factory()->create(['display_name' => 'Newest']);

        $oldestRelation = UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $oldest->id,
            'status' => UserRelationStatus::Following,
        ]);
        $oldestRelation->created_at = Carbon::parse('2026-01-01');
        $oldestRelation->save();

        $middleRelation = UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $middle->id,
            'status' => UserRelationStatus::Following,
        ]);
        $middleRelation->created_at = Carbon::parse('2026-04-01');
        $middleRelation->save();

        $newestRelation = UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $newest->id,
            'status' => UserRelationStatus::Following,
        ]);
        $newestRelation->created_at = Carbon::parse('2026-06-01');
        $newestRelation->save();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertSuccessful();
        $names = array_map(
            fn (array $row) => $row['attributes']['displayName'],
            $response->json('data'),
        );
        $this->assertEquals(['Newest', 'Middle', 'Oldest'], $names);
    }

    public function testItDefaultsToFollowedAtDescendingOnFollowers(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $oldest = User::factory()->create(['display_name' => 'OldestFollower']);
        $newest = User::factory()->create(['display_name' => 'NewestFollower']);

        $oldestRelation = UserRelation::create([
            'user_id' => $oldest->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);
        $oldestRelation->created_at = Carbon::parse('2026-02-01');
        $oldestRelation->save();

        $newestRelation = UserRelation::create([
            'user_id' => $newest->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);
        $newestRelation->created_at = Carbon::parse('2026-05-01');
        $newestRelation->save();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        // Assert
        $response->assertSuccessful();
        $names = array_map(
            fn (array $row) => $row['attributes']['displayName'],
            $response->json('data'),
        );
        $this->assertEquals(['NewestFollower', 'OldestFollower'], $names);
    }

    public function testItCanSortFollowingByDisplayedUserPoints(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $casual = User::factory()->create([
            'display_name' => 'Casual',
            'points' => 100,
        ]);
        $regular = User::factory()->create([
            'display_name' => 'Regular',
            'points' => 200,
        ]);
        $veteran = User::factory()->create([
            'display_name' => 'Veteran',
            'points' => 300,
        ]);

        foreach ([$regular, $veteran, $casual] as $followed) {
            UserRelation::create([
                'user_id' => $auth->id,
                'related_user_id' => $followed->id,
                'status' => UserRelationStatus::Following,
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following?sort=-points");

        // Assert
        $response->assertSuccessful();
        $this->assertDisplayNamesOrder(['Veteran', 'Regular', 'Casual'], $response->json('data'));
    }

    public function testItCanSortFollowersByDisplayedUserDisplayName(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $alpha = User::factory()->create([
            'display_name' => 'FollowerAlpha',
        ]);
        $bravo = User::factory()->create([
            'display_name' => 'FollowerBravo',
        ]);
        $charlie = User::factory()->create([
            'display_name' => 'FollowerCharlie',
        ]);

        foreach ([$charlie, $alpha, $bravo] as $follower) {
            UserRelation::create([
                'user_id' => $follower->id,
                'related_user_id' => $auth->id,
                'status' => UserRelationStatus::Following,
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers?sort=displayName");

        // Assert
        $response->assertSuccessful();
        $this->assertDisplayNamesOrder(['FollowerAlpha', 'FollowerBravo', 'FollowerCharlie'], $response->json('data'));
    }

    public function testItExcludesBlockedAndNotFollowingRows(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $followed = User::factory()->create(['display_name' => 'Followed']);
        $blocked = User::factory()->create(['display_name' => 'Blocked']);
        $notFollowing = User::factory()->create(['display_name' => 'NotFollowing']);

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $followed->id,
            'status' => UserRelationStatus::Following,
        ]);
        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $blocked->id,
            'status' => UserRelationStatus::Blocked,
        ]);
        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $notFollowing->id,
            'status' => UserRelationStatus::NotFollowing,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertSuccessful();
        $names = array_map(
            fn (array $row) => $row['attributes']['displayName'],
            $response->json('data'),
        );
        $this->assertEquals(['Followed'], $names);
    }

    public function testItExcludesRowsWhereOtherUserIsBanned(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $active = User::factory()->create(['display_name' => 'Active']);
        $banned = User::factory()->create([
            'display_name' => 'Banned',
            'banned_at' => Carbon::now(),
        ]);

        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $active->id,
            'status' => UserRelationStatus::Following,
        ]);
        UserRelation::create([
            'user_id' => $auth->id,
            'related_user_id' => $banned->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertSuccessful();
        $names = array_map(
            fn (array $row) => $row['attributes']['displayName'],
            $response->json('data'),
        );
        $this->assertEquals(['Active'], $names);
    }

    public function testItExcludesFollowerRowsWhereTheFollowerIsBanned(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $activeFollower = User::factory()->create(['display_name' => 'ActiveFollower']);
        $bannedFollower = User::factory()->create([
            'display_name' => 'BannedFollower',
            'banned_at' => Carbon::now(),
        ]);

        UserRelation::create([
            'user_id' => $activeFollower->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);
        UserRelation::create([
            'user_id' => $bannedFollower->id,
            'related_user_id' => $auth->id,
            'status' => UserRelationStatus::Following,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/followers");

        // Assert
        $response->assertSuccessful();
        $names = array_map(
            fn (array $row) => $row['attributes']['displayName'],
            $response->json('data'),
        );
        $this->assertEquals(['ActiveFollower'], $names);
    }

    public function testItPaginatesWithA50PerPageDefault(): void
    {
        // Arrange
        $auth = User::factory()->create(['web_api_key' => 'test-key']);
        $others = User::factory()->count(63)->create();
        foreach ($others as $other) {
            UserRelation::create([
                'user_id' => $auth->id,
                'related_user_id' => $other->id,
                'status' => UserRelationStatus::Following,
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-follows')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$auth->ulid}/following");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));

        $pageMeta = $response->json('meta.page');
        $this->assertEquals(63, $pageMeta['total']);
        $this->assertEquals(50, $pageMeta['perPage']);
        $this->assertEquals(1, $pageMeta['currentPage']);
    }

    /**
     * @param list<string> $expected
     * @param list<array{attributes: array{displayName: string}}> $data
     */
    private function assertDisplayNamesOrder(array $expected, array $data): void
    {
        $this->assertEquals(
            $expected,
            array_map(
                fn (array $row) => $row['attributes']['displayName'],
                $data,
            ),
        );
    }
}
