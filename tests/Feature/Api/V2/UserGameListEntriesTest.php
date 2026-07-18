<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class UserGameListEntriesTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    private function makeFriends(User $a, User $b): void
    {
        UserRelation::factory()->following()->create([
            'user_id' => $a->id,
            'related_user_id' => $b->id,
        ]);
        UserRelation::factory()->following()->create([
            'user_id' => $b->id,
            'related_user_id' => $a->id,
        ]);
    }

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $target = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItForbidsDefaultPlayListAccessToUnrelatedAuthenticatedCaller(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $target = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries");

        // Assert
        $response->assertForbidden();
    }

    public function testItReturns404ForNonexistentUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/does-not-exist/user-game-list-entries');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404ForBannedUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $banned = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$banned->ulid}/user-game-list-entries");

        // Assert
        $response->assertNotFound();
    }

    public function testItDefaultsToPlayKindForSelf(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $playEntry = UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);
        UserGameListEntry::factory()->setRequest()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $playEntry->id, $data[0]['id']);
    }

    public function testItFiltersToPlayKindForSelf(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $playEntry = UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);
        UserGameListEntry::factory()->setRequest()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[kind]=play");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $playEntry->id, $data[0]['id']);
    }

    public function testItFiltersToAchievementSetRequestKindForSelf(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);
        $requestEntry = UserGameListEntry::factory()->setRequest()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[kind]=achievement_set_request");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $requestEntry->id, $data[0]['id']);
    }

    public function testItReturnsPlayEntriesToFriends(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $target = User::factory()->create();
        $friend = User::factory()->create(['web_api_key' => 'test-key']);
        $this->makeFriends($target, $friend);
        $playEntry = UserGameListEntry::factory()->play()->for($target)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries?filter[kind]=play");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $playEntry->id, $data[0]['id']);
    }

    public function testItForbidsPlayListAccessToNonFriend(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $target = User::factory()->create();
        User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->play()->for($target)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries?filter[kind]=%20play%20");

        // Assert
        $response->assertForbidden();
    }

    public function testItReturnsSetRequestsToNonFriend(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $target = User::factory()->create();
        User::factory()->create(['web_api_key' => 'test-key']);
        $requestEntry = UserGameListEntry::factory()->setRequest()->for($target)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries?filter[kind]=achievement_set_request");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $requestEntry->id, $data[0]['id']);
    }

    public function testItForbidsDefaultPlayListAccessToNonFriend(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $target = User::factory()->create();
        User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->play()->for($target)->create(['game_id' => $game->id]);
        UserGameListEntry::factory()->setRequest()->for($target)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries");

        // Assert
        $response->assertForbidden();
    }

    public function testItReturnsDevelopKindEntriesToSelf(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $developEntry = UserGameListEntry::factory()->develop()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[kind]=develop");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $developEntry->id, $data[0]['id']);
    }

    public function testItForbidsDevelopListAccessToAnotherUser(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $target = User::factory()->create();
        User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->develop()->for($target)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$target->ulid}/user-game-list-entries?filter[kind]=develop");

        // Assert
        $response->assertForbidden();
    }

    public function testItReturns400ForUnknownKindFilter(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[kind]=garbage");

        // Assert
        $response->assertStatus(400);
    }

    public function testItReturns400ForCommaSeparatedKindFilter(): void
    {
        // Arrange
        $self = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[kind]=play,achievement_set_request");

        // Assert
        $response->assertStatus(400);
    }

    public function testItCanFilterByGameId(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $entry1 = UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game1->id]);
        UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game2->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[gameId]={$game1->id}");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $entry1->id, $data[0]['id']);
    }

    public function testItCombinesGameIdAndKindFilters(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $playEntry = UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);
        UserGameListEntry::factory()->setRequest()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?filter[gameId]={$game->id}&filter[kind]=play");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $playEntry->id, $data[0]['id']);
    }

    public function testItSortsByCreatedAtDescendingByDefault(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $older = UserGameListEntry::factory()->play()->for($self)->create([
            'game_id' => $game->id,
            'created_at' => now()->subDays(3),
        ]);
        $middle = UserGameListEntry::factory()->play()->for($self)->create([
            'game_id' => Game::factory()->create(['system_id' => $system->id])->id,
            'created_at' => now()->subDay(),
        ]);
        $newest = UserGameListEntry::factory()->play()->for($self)->create([
            'game_id' => Game::factory()->create(['system_id' => $system->id])->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals([
            (string) $newest->id,
            (string) $middle->id,
            (string) $older->id,
        ], $ids);
    }

    public function testItSortsAscendingWhenRequested(): void
    {
        // Arrange
        $system = System::factory()->create();
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $older = UserGameListEntry::factory()->play()->for($self)->create([
            'game_id' => Game::factory()->create(['system_id' => $system->id])->id,
            'created_at' => now()->subDays(2),
        ]);
        $newer = UserGameListEntry::factory()->play()->for($self)->create([
            'game_id' => Game::factory()->create(['system_id' => $system->id])->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?sort=createdAt");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals([
            (string) $older->id,
            (string) $newer->id,
        ], $ids);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        $system = System::factory()->create();
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $games = Game::factory()->count(60)->create(['system_id' => $system->id]);
        foreach ($games as $index => $game) {
            UserGameListEntry::factory()->play()->for($self)->create([
                'game_id' => $game->id,
                'created_at' => now()->subMinutes($index),
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(60, $response->json('meta.page.total'));
    }

    public function testItHonorsPageNumberAndSize(): void
    {
        // Arrange
        $system = System::factory()->create();
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $games = Game::factory()->count(25)->create(['system_id' => $system->id]);
        foreach ($games as $index => $game) {
            UserGameListEntry::factory()->play()->for($self)->create([
                'game_id' => $game->id,
                'created_at' => now()->subMinutes($index),
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?page[number]=2&page[size]=10");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.page.currentPage'));
        $this->assertEquals(10, $response->json('meta.page.perPage'));
    }

    public function testItRejectsPageSizeAboveMax(): void
    {
        // Arrange
        $self = User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?page[size]=10000");

        // Assert
        $response->assertStatus(400);
    }

    public function testItCanIncludeGameRelationship(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Super Mario Bros.',
        ]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?include=game");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals('Super Mario Bros.', $included[0]['attributes']['title']);
    }

    public function testItReturnsEntryAttributesAndType(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo Entertainment System']);
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Super Mario Bros.',
            'image_icon_asset_path' => '/Images/000001.png',
            'points_total' => 123,
            'achievements_published' => 12,
        ]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        $entry = UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries");

        // Assert
        $response->assertSuccessful();
        $row = $response->json('data.0');
        $this->assertEquals((string) $entry->id, $row['id']);
        $this->assertEquals('user-game-list-entries', $row['type']);
        $this->assertArrayNotHasKey('kind', $row['attributes']);
        $this->assertArrayHasKey('createdAt', $row['attributes']);
        $this->assertEquals($game->id, $row['attributes']['gameId']);
        $this->assertEquals('Super Mario Bros.', $row['attributes']['gameTitle']);
        $this->assertEquals(media_asset('/Images/000001.png'), $row['attributes']['gameIconUrl']);
        $this->assertEquals($system->id, $row['attributes']['systemId']);
        $this->assertEquals('Nintendo Entertainment System', $row['attributes']['systemName']);
        $this->assertEquals(123, $row['attributes']['pointsTotal']);
        $this->assertEquals(12, $row['attributes']['achievementsPublished']);
        $this->assertArrayNotHasKey('user', $row['relationships'] ?? []);
        $this->assertArrayNotHasKey('game', $row['relationships'] ?? []);
        $this->assertArrayNotHasKey('links', $row);
    }

    public function testItExposesGameRelationshipPointerWhenIncluded(): void
    {
        /**
         * Without ?include=game, showDataIfLoaded() keeps the game pointer out
         * of the relationships block. With ?include=game, the pointer appears
         * and the data ID matches the loaded game.
         */

        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $self = User::factory()->create(['web_api_key' => 'test-key']);
        UserGameListEntry::factory()->play()->for($self)->create(['game_id' => $game->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-game-list-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$self->ulid}/user-game-list-entries?include=game");

        // Assert
        $response->assertSuccessful();
        $row = $response->json('data.0');
        $this->assertArrayHasKey('game', $row['relationships']);
        $this->assertEquals((string) $game->id, $row['relationships']['game']['data']['id']);
    }
}
