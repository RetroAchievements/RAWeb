<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class LeaderboardEntriesTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesEntriesForLeaderboard(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::Score,
            'rank_asc' => false, // higher is better
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $entry1 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user1->id,
            'score' => 1000,
        ]);
        $entry2 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
            'score' => 500,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $entry1->id, $ids);
        $this->assertContains((string) $entry2->id, $ids);
    }

    public function testItReturns404WhenLeaderboardDoesNotExist(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/leaderboards/99999/entries');

        // Assert
        $response->assertNotFound();
    }

    public function testItSortsEntriesByRankAscending(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::Score,
            'rank_asc' => false, // higher score is better (rank 1)
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user1->id,
            'score' => 500, // rank 3
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
            'score' => 1000, // rank 1
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user3->id,
            'score' => 750, // rank 2
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $ranks = collect($data)->pluck('attributes.rank')->toArray();

        $this->assertEquals([1, 2, 3], $ranks); // ordered by rank
    }

    public function testItHandlesTiedRanks(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::Score,
            'rank_asc' => false, // higher score is better
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // User2 submitted first, user1 submitted second (both tied at 1000).
        $entry2 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
            'score' => 1000, // rank 1 (tied, but submitted first)
            'created_at' => now()->subMinutes(10),
        ]);
        $entry1 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user1->id,
            'score' => 1000, // rank 1 (tied, but submitted second)
            'created_at' => now()->subMinutes(5),
        ]);
        $entry3 = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user3->id,
            'score' => 500, // rank 3 (after tied entries)
            'created_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $ranks = collect($data)->pluck('attributes.rank')->toArray();
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertEquals([1, 1, 3], $ranks);

        // ... ties are broken by created_at ...
        $this->assertEquals([
            (string) $entry2->id, // submitted first!
            (string) $entry1->id,
            (string) $entry3->id,
        ], $ids);
    }

    public function testItCanIncludeUserRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        $player = User::factory()->create(['display_name' => 'TopPlayer']);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $player->id,
            'score' => 1000,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries?include=user");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('users', $included[0]['type']);
        $this->assertEquals('TopPlayer', $included[0]['attributes']['displayName']);
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        $users = User::factory()->count(100)->create();
        foreach ($users as $index => $user) {
            LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'score' => 1000 - $index,
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(100, $response->json('meta.page.total'));
    }

    public function testItCanFilterByUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        $targetUser = User::factory()->create(['display_name' => 'TargetPlayer']);
        $otherUser = User::factory()->create();

        $targetEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $targetUser->id,
            'score' => 1000,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $otherUser->id,
            'score' => 500,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries?filter[user]=TargetPlayer");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $targetEntry->id, $data[0]['id']);
    }

    public function testItPreservesRankWhenFilteringByUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::Score,
            'rank_asc' => false, // !! higher score is better
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create(['display_name' => 'MiddlePlayer']);
        $user3 = User::factory()->create();

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user1->id,
            'score' => 1000, // rank 1
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
            'score' => 750, // rank 2
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user3->id,
            'score' => 500, // rank 3
        ]);

        // Act
        // ... filter to user2 who should still be rank 2, not rank 1 due to the applied filter ...
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries?filter[user]=MiddlePlayer");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);

        $this->assertEquals(2, $data[0]['attributes']['rank']);
    }

    public function testItReturnsFormattedScore(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::TimeCentiseconds,
            'rank_asc' => true, // !!
        ]);

        $user = User::factory()->create();
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
            'score' => 12345, // 2:03.45
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');
        $this->assertEquals(12345, $attributes['score']);
        $this->assertEquals('2:03.45', $attributes['formattedScore']);
    }

    public function testItExcludesEntriesFromHiddenLeaderboards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $hiddenLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => -1, // !!
        ]);

        $user = User::factory()->create();
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $hiddenLeaderboard->id,
            'user_id' => $user->id,
            'score' => 1000,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$hiddenLeaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $this->assertEmpty($response->json('data'));
    }

    public function testItExcludesEntriesFromHubGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Hubs]);
        $hubGame = Game::factory()->create(['system_id' => System::Hubs]);

        $hubLeaderboard = Leaderboard::factory()->create([
            'game_id' => $hubGame->id,
            'order_column' => 1,
        ]);

        $user = User::factory()->create();
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $hubLeaderboard->id,
            'user_id' => $user->id,
            'score' => 1000,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$hubLeaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $this->assertEmpty($response->json('data'));
    }

    public function testItExcludesEntriesFromEventGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        System::factory()->create(['id' => System::Events]);
        $eventGame = Game::factory()->create(['system_id' => System::Events]);

        $eventLeaderboard = Leaderboard::factory()->create([
            'game_id' => $eventGame->id,
            'order_column' => 1,
        ]);

        $user = User::factory()->create();
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $eventLeaderboard->id,
            'user_id' => $user->id,
            'score' => 1000,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$eventLeaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $this->assertEmpty($response->json('data'));
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::Score,
        ]);

        $user = User::factory()->create();
        $entry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
            'score' => 12345,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertEquals(12345, $attributes['score']);
        $this->assertEquals('012345', $attributes['formattedScore']); // pads to 6 digits
        $this->assertEquals(1, $attributes['rank']);
        $this->assertArrayHasKey('createdAt', $attributes);
        $this->assertArrayHasKey('updatedAt', $attributes);
    }

    public function testItDoesNotIncludeSelfLinks(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        $user = User::factory()->create();
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItHandlesLowerIsBetterLeaderboards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'order_column' => 1,
            'format' => ValueFormat::TimeSeconds,
            'rank_asc' => true, // !!
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user1->id,
            'score' => 300, // slowest, should be rank 3
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
            'score' => 100, // fastest, should be rank 1
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user3->id,
            'score' => 200, // middle, should be rank 2
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('leaderboard-entries')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/leaderboards/{$leaderboard->id}/entries");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $scores = collect($data)->pluck('attributes.score')->toArray();

        $this->assertEquals([100, 200, 300], $scores);
    }
}
