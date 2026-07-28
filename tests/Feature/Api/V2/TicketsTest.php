<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\TicketableType;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class TicketsTest extends TestCase
{
    use MakesJsonApiRequests;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    /**
     * @return array{system: System, game: Game, achievement: Achievement, ticketAuthor: User}
     */
    private function makeGameContext(): array
    {
        $system = System::factory()->create(['name' => 'Nintendo Entertainment System']);
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/123456.png',
        ]);
        $ticketAuthor = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => $ticketAuthor->id,
        ]);

        return ['system' => $system, 'game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $ticketAuthor];
    }

    public function testItDefaultsToOpenStateOnly(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $openTicket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => $author->id,
        ]);
        $requestTicket = Ticket::factory()->forAchievement($achievement)->request()->create([
            'ticketable_author_id' => $author->id,
        ]);
        Ticket::factory()->forAchievement($achievement)->resolved()->create([
            'ticketable_author_id' => $author->id,
        ]);
        Ticket::factory()->forAchievement($achievement)->closed()->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'tickets', 'id' => (string) $requestTicket->id],
            ['type' => 'tickets', 'id' => (string) $openTicket->id],
        ]);
        $this->assertCount(2, $response->json('data'));
    }

    public function testItReturnsAllStatesWhenFilterStateIsAll(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        Ticket::factory()->forAchievement($achievement)->open()->create(['ticketable_author_id' => $author->id]);
        Ticket::factory()->forAchievement($achievement)->resolved()->create(['ticketable_author_id' => $author->id]);
        Ticket::factory()->forAchievement($achievement)->closed()->create(['ticketable_author_id' => $author->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[state]=all');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json('data'));
    }

    public function testItFiltersByCommaSeparatedStates(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        Ticket::factory()->forAchievement($achievement)->open()->create(['ticketable_author_id' => $author->id]);
        $resolved = Ticket::factory()->forAchievement($achievement)->resolved()->create(['ticketable_author_id' => $author->id]);
        $closed = Ticket::factory()->forAchievement($achievement)->closed()->create(['ticketable_author_id' => $author->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[state]=resolved,closed');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $expected = collect([$resolved->id, $closed->id])->map(fn ($id) => (string) $id)->sort()->values()->all();
        $this->assertEquals($expected, $ids);
    }

    public function testItRejectsMalformedGameIdFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[gameId]=abc');

        // Assert
        $response->assertStatus(400);
    }

    public function testItRejectsMalformedAchievementIdFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[achievementId]=abc');

        $mixedResponse = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[achievementId]=123,abc');

        // Assert
        $response->assertStatus(400);
        $mixedResponse->assertStatus(400);
    }

    public function testItRejectsMalformedLeaderboardIdFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[leaderboardId]=abc');

        // Assert
        $response->assertStatus(400);
    }

    public function testItRejectsUnknownStateFilterValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[state]=garbage');

        // Assert
        $response->assertStatus(400);
    }

    public function testItFiltersByType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $didNotTrigger = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'type' => TicketType::DidNotTrigger,
        ]);
        Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'type' => TicketType::TriggeredAtWrongTime,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[type]=did_not_trigger');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $didNotTrigger->id, $response->json('data.0.id'));
    }

    public function testItFiltersByTicketableType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        Ticket::factory()->forAchievement($achievement)->create(['ticketable_author_id' => $author->id]);
        $leaderboardTicket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[ticketableType]=leaderboard');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $leaderboardTicket->id, $response->json('data.0.id'));
        $this->assertEquals(TicketableType::Leaderboard->value, $response->json('data.0.attributes.ticketableType'));
    }

    public function testItResolvesReporterUlidsAndIgnoresUnknownOnes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $reporter1 = User::factory()->create();
        $reporter2 = User::factory()->create();
        User::factory()->create();

        $ticket1 = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'reporter_id' => $reporter1->id,
        ]);
        $ticket2 = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'reporter_id' => $reporter2->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets?filter[reporterUserId]={$reporter1->ulid},{$reporter2->ulid}");

        $unknownResponse = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[reporterUserId]=NOT_A_REAL_ULID');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $expected = collect([$ticket1->id, $ticket2->id])->map(fn ($id) => (string) $id)->sort()->values()->all();
        $this->assertEquals($expected, $ids);

        $unknownResponse->assertSuccessful();
        $this->assertCount(0, $unknownResponse->json('data'));
    }

    public function testAchievementIdFilterDoesNotLeakLeaderboardTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $leaderboard = Leaderboard::factory()->create([
            'id' => $achievement->id, // !! collides with the achievement's id on purpose
            'game_id' => $game->id,
        ]);

        $achievementTicket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
        ]);
        Ticket::factory()->forLeaderboard($leaderboard)->create(['ticketable_author_id' => $author->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets?filter[achievementId]={$achievement->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $achievementTicket->id, $response->json('data.0.id'));
    }

    public function testLeaderboardIdFilterDoesNotLeakAchievementTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $leaderboard = Leaderboard::factory()->create([
            'id' => $achievement->id, // !! collides with the achievement's id
            'game_id' => $game->id,
        ]);

        Ticket::factory()->forAchievement($achievement)->create(['ticketable_author_id' => $author->id]);
        $leaderboardTicket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets?filter[leaderboardId]={$leaderboard->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $leaderboardTicket->id, $response->json('data.0.id'));
    }

    public function testItHidesQuarantinedTicketsFromNonManagersOnIndex(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $openTicket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => $author->id,
        ]);
        Ticket::factory()->forAchievement($achievement)->quarantined()->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?filter[state]=all');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $openTicket->id, $response->json('data.0.id'));
    }

    public function testItInlinesAchievementContextOnAchievementTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['system' => $system, 'game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $reporter = User::factory()->create(['display_name' => 'TestReporter']);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'reporter_id' => $reporter->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets/{$ticket->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');
        $this->assertEquals(TicketableType::Achievement->value, $attributes['ticketableType']);
        $this->assertEquals($achievement->id, $attributes['ticketableId']);
        $this->assertEquals($achievement->title, $attributes['ticketableTitle']);
        $this->assertEquals($game->id, $attributes['gameId']);
        $this->assertEquals($game->title, $attributes['gameTitle']);
        $this->assertEquals($game->badge_url, $attributes['gameIconUrl']);
        $this->assertEquals($system->name, $attributes['systemName']);
        $this->assertEquals('TestReporter', $attributes['reporterDisplayName']);
    }

    public function testItInlinesLeaderboardContextOnLeaderboardTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['system' => $system, 'game' => $game, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'title' => 'Speedrun',
        ]);

        $ticket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets/{$ticket->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');
        $this->assertEquals(TicketableType::Leaderboard->value, $attributes['ticketableType']);
        $this->assertEquals($leaderboard->id, $attributes['ticketableId']);
        $this->assertEquals('Speedrun', $attributes['ticketableTitle']);
        $this->assertEquals($game->id, $attributes['gameId']);
        $this->assertEquals($game->title, $attributes['gameTitle']);
        $this->assertEquals($game->badge_url, $attributes['gameIconUrl']);
        $this->assertEquals($system->name, $attributes['systemName']);
    }

    public function testItDoesNotEmitRelationshipsWithoutInclude(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets/{$ticket->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('relationships', $response->json('data'));
    }

    public function testItEmitsAchievementRelationshipOnlyForAchievementTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        $leaderboardTicket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets/{$leaderboardTicket->id}?include=achievement");

        // Assert
        $response->assertSuccessful();
        $relationships = $response->json('data.relationships') ?? [];
        $this->assertArrayNotHasKey('achievement', $relationships);
    }

    public function testItSupportsNestedAchievementGameIncludePath(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create();
        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        $author = User::factory()->create();
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $author->id,
        ]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?include=achievement.games');

        // Assert
        $response->assertSuccessful();
        $this->assertEquals((string) $ticket->id, $response->json('data.0.id'));
        $this->assertEquals((string) $achievement->id, $response->json('data.0.relationships.achievement.data.id'));

        $included = collect($response->json('included'));
        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'achievements' && $resource['id'] === (string) $achievement->id));
        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'games' && $resource['id'] === (string) $game->id));
    }

    public function testItSupportsNestedLeaderboardGameIncludePath(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?include=leaderboard.games');

        // Assert
        $response->assertSuccessful();
        $this->assertEquals((string) $ticket->id, $response->json('data.0.id'));
        $this->assertEquals((string) $leaderboard->id, $response->json('data.0.relationships.leaderboard.data.id'));

        $included = collect($response->json('included'));
        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'leaderboards' && $resource['id'] === (string) $leaderboard->id));
        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'games' && $resource['id'] === (string) $game->id));
    }

    public function testAchievementsRelationshipEndpointReturnsOnlyThatAchievementsTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();
        $otherAchievement = Achievement::factory()->create();

        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
        ]);
        Ticket::factory()->forAchievement($otherAchievement)->create([
            'ticketable_author_id' => $otherAchievement->user_id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievements/{$achievement->id}/tickets");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $ticket->id, $response->json('data.0.id'));
    }

    public function testGamesRelationshipEndpointReturnsBothTicketableTypes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        $achTicket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
        ]);
        $lbTicket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // ... a ticket on a different game's content should not be returned ...
        $otherGame = Game::factory()->create(['system_id' => $game->system_id]);
        $otherAchievement = Achievement::factory()->create(['game_id' => $otherGame->id]);
        Ticket::factory()->forAchievement($otherAchievement)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/tickets");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $expected = collect([$achTicket->id, $lbTicket->id])->map(fn ($id) => (string) $id)->sort()->values()->all();
        $this->assertEquals($expected, $ids);
    }

    public function testGamesRelationshipEndpointSupportsTicketableTypeFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);

        Ticket::factory()->forAchievement($achievement)->create(['ticketable_author_id' => $author->id]);
        $lbTicket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/tickets?filter[ticketableType]=leaderboard");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $lbTicket->id, $response->json('data.0.id'));
    }

    public function testUsersRelationshipEndpointReturnsAuthoredTickets(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['game' => $game, 'achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $author->id,
        ]);

        // ... the author has both an achievement ticket and a leaderboard ticket ...
        $achTicket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
        ]);
        $lbTicket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'ticketable_author_id' => $author->id,
        ]);

        // ... a ticket where the author is the reporter but not the ticketable_author should be excluded ...
        $otherAuthor = User::factory()->create();
        $otherAchievement = Achievement::factory()->create(['user_id' => $otherAuthor->id]);
        Ticket::factory()->forAchievement($otherAchievement)->create([
            'ticketable_author_id' => $otherAuthor->id,
            'reporter_id' => $author->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$author->ulid}/tickets?filter[state]=all");

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $expected = collect([$achTicket->id, $lbTicket->id])->map(fn ($id) => (string) $id)->sort()->values()->all();
        $this->assertEquals($expected, $ids);
    }

    public function testItSortsByReportedAtDescendingByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $older = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'created_at' => Carbon::parse('2024-01-01 00:00:00'),
        ]);
        $newer = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'created_at' => Carbon::parse('2026-01-01 00:00:00'),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $newer->id, (string) $older->id], $ids);
    }

    public function testItSortsByTicketId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $first = Ticket::factory()->forAchievement($achievement)->create(['ticketable_author_id' => $author->id]);
        $second = Ticket::factory()->forAchievement($achievement)->create(['ticketable_author_id' => $author->id]);
        $third = Ticket::factory()->forAchievement($achievement)->create(['ticketable_author_id' => $author->id]);

        // Act
        $ascending = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?sort=id');

        $descending = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/tickets?sort=-id');

        // Assert
        $ascending->assertSuccessful();
        $this->assertEquals(
            [(string) $first->id, (string) $second->id, (string) $third->id],
            collect($ascending->json('data'))->pluck('id')->all(),
        );

        $descending->assertSuccessful();
        $this->assertEquals(
            [(string) $third->id, (string) $second->id, (string) $first->id],
            collect($descending->json('data'))->pluck('id')->all(),
        );
    }

    public function testStateAndTypeSerializeAsStringEnumValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievement' => $achievement, 'ticketAuthor' => $author] = $this->makeGameContext();

        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $author->id,
            'type' => TicketType::DidNotTrigger,
            'state' => TicketState::Open,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('tickets')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/tickets/{$ticket->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('open', $response->json('data.attributes.state'));
        $this->assertEquals('did_not_trigger', $response->json('data.attributes.type'));
    }
}
