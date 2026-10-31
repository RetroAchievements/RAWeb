<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\ClientSupportLevel;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Emulator;
use App\Models\EmulatorCoreRestriction;
use App\Models\EmulatorUserAgent;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketStatusUpdatedNotification;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('given the reporter is muted, it prevents ticket creation', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
        'muted_until' => Carbon::parse('2035-01-01'), // !!
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RetroArch',
        'emulatorVersion' => '1.16.0',
        'core' => 'mupen64plus',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertForbidden();
});

it('given the reporter account is too new, it prevents ticket creation', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now(), // !!
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RetroArch',
        'emulatorVersion' => '1.16.0',
        'core' => 'mupen64plus',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertForbidden();
});

it('given a valid submission, it creates the ticket', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
    $emulator = Emulator::factory()->create(['name' => 'RetroArch']);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RetroArch',
        'emulatorVersion' => '1.16.0',
        'core' => 'mupen64plus',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'ticketable_author_id' => $developer->id,
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'type' => TicketType::TriggeredAtWrongTime,
        'hardcore' => 1,
        'emulator_id' => $emulator->id,
        'emulator_version' => '1.16.0',
        'emulator_core' => 'mupen64plus',
        'game_hash_id' => $gameHash->id,
        'body' => 'Test description', // emulator data is not captured when an emulator record is found
    ]);
});

it('given rich presence and an emulator with no record, it formats the ticket note', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RetroArch',
        'emulatorVersion' => '1.16.0',
        'core' => 'mupen64plus',
        'gameHashId' => $gameHash->id,
        'extra' => 'eyJ0cmlnZ2VyUmljaFByZXNlbmNlIjoi8J+Qukxpbmsg8J+Xuu+4j0RlYXRoIE1vdW50YWluIOKdpO+4jzMvMyDwn5GlMS80IPCfp78wLzQg8J+RuzAvNjAg8J+QnDAvMjQg8J+SgDUg8J+VmTEyOjAwIEFN8J+MmSJ9',
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'body' => "Test description\n\n" .
            "Rich Presence at time of trigger:\n" .
            "🐺Link 🗺️Death Mountain ❤️3/3 👥1/4 🧿0/4 👻0/60 🐜0/24 💀5 🕙12:00 AM🌙\n" .
            "Emulator: RetroArch (mupen64plus)\n" .
            'Emulator Version: 1.16.0',
    ]);
});

it('given the reporter already has an open ticket, it returns a conflict instead of a duplicate', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // !!! the user already has a ticket open !!!
    $existingTicket = Ticket::factory()->create([
        'reporter_id' => $user->id,
        'ticketable_id' => $achievement->id,
        'state' => TicketState::Open,
    ]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RetroArch',
        'emulatorVersion' => '1.9.0',
        'core' => 'genesis_plus_gx',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response
        ->assertStatus(409)
        ->assertJson([
            'message' => __('legacy.error.ticket_exists'),
            'ticketId' => $existingTicket->id,
        ]);
});

it('given an emulator with no record and no core, it embeds the emulator info in the note', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RAP64',
        'emulatorVersion' => '1.9.0',
        // 'core' => 'genesis_plus_gx', !! commented this out on purpose, the user isn't sending it on submit
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'body' => "Test description\n\n" .
            "Emulator: RAP64\n" .
            'Emulator Version: 1.9.0',
    ]);
});

it('given a restricted core, it quarantines the ticket', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Game Boy', 'active' => true]);
    $game = Game::factory()->create(['title' => 'Batman', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
    $emulator = Emulator::factory()->create(['name' => 'RetroArch']);

    EmulatorUserAgent::create([
        'emulator_id' => $emulator->id,
        'client' => 'RetroArch',
    ]);

    EmulatorCoreRestriction::create([
        'core_name' => 'doublecherrygb_libretro',
        'support_level' => ClientSupportLevel::Warned,
        'notes' => 'known issues',
    ]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
    PlayerSession::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'game_hash_id' => $gameHash->id,
        'user_agent' => 'RetroArch/1.19.1 (Windows) doublecherrygb_libretro/5d2ac21',
    ]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'RetroArch',
        'emulatorVersion' => '1.19.1',
        'core' => 'doublecherrygb_libretro',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'state' => TicketState::Quarantined,
    ]);
});

it('given a casual-only emulator, it quarantines the ticket', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
    $emulator = Emulator::factory()->create(['name' => 'gopher64', 'softcore_only' => true]);

    EmulatorUserAgent::create([
        'emulator_id' => $emulator->id,
        'client' => 'gopher64',
    ]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
    PlayerSession::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'game_hash_id' => $gameHash->id,
        'user_agent' => 'gopher64/1.15.0 (Windows)',
    ]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'casual',
        'issue' => TicketType::TriggeredAtWrongTime->value,
        'description' => 'Test description',
        'emulator' => 'gopher64',
        'emulatorVersion' => '1.15.0',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'state' => TicketState::Quarantined,
    ]);
});

it('given an emulator that cannot debug triggers and no player session, it quarantines the ticket', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'PlayStation 2', 'active' => true]);
    $game = Game::factory()->create(['title' => 'Resident Evil 4', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    Emulator::factory()->create(['name' => 'AetherSX2', 'can_debug_triggers' => false]);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // ... no player session ...

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::DidNotTrigger->value,
        'description' => 'Test description',
        'emulator' => 'AetherSX2',
        'emulatorVersion' => '1.5-4248',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'state' => TicketState::Quarantined,
    ]);
});

it('given a subset achievement, a session only on the parent game, and an emulator that cannot debug triggers, it quarantines the ticket', function () {
    // Arrange
    $system = System::factory()->create(['name' => 'PlayStation 2', 'active' => true]);
    $game = Game::factory()->create(['title' => 'Resident Evil 4', 'system_id' => $system->id]);
    $subset = Game::factory()->create(['title' => 'Resident Evil 4 [Subset - Bonus]', 'system_id' => $system->id]);
    $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $subset->id, 'user_id' => $developer->id]);

    $emulator = Emulator::factory()->create(['name' => 'AetherSX2', 'can_debug_triggers' => false]);
    EmulatorUserAgent::create(['emulator_id' => $emulator->id, 'client' => 'AetherSX2']);

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'created_at' => Carbon::now()->subWeeks(2),
    ]);
    $this->actingAs($user);

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
    PlayerSession::factory()->create([
        'user_id' => $user->id,
        'game_id' => $game->id,
        'game_hash_id' => $gameHash->id,
        'user_agent' => 'AetherSX2/1.5-4248 (Android)',
    ]);

    // Act
    $response = $this->postJson(route('api.ticket.store'), [
        'ticketableModel' => 'achievement',
        'ticketableId' => $achievement->id,
        'mode' => 'hardcore',
        'issue' => TicketType::DidNotTrigger->value,
        'description' => 'Test description',
        'emulator' => 'AetherSX2',
        'emulatorVersion' => '1.5-4248',
        'gameHashId' => $gameHash->id,
    ]);

    // Assert
    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'state' => TicketState::Quarantined,
    ]);
});

/**
 * @return array{developer: User, game: Game, tickets: Ticket[]}
 */
function createTicketIndexFixture(int $ticketCount = 3): array
{
    $developer = User::factory()->create();
    $reporter = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    $tickets = [];
    for ($i = 0; $i < $ticketCount; $i++) {
        $tickets[] = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => $developer->id,
            'reporter_id' => $reporter->id,
            'created_at' => Carbon::parse('2024-01-01')->addHours($i),
        ]);
    }

    return ['developer' => $developer, 'game' => $game, 'tickets' => $tickets];
}

describe('index', function () {
    it('given a guest, they are unauthorized', function () {
        // ARRANGE
        $fixture = createTicketIndexFixture();

        // ACT
        $response = $this->getJson(route('api.ticket.index', ['scope' => 'game', 'game' => $fixture['game']->id]));

        // ASSERT
        $response->assertUnauthorized();
    });

    it('given the scope is game, tickets associated with other games are excluded', function () {
        // ARRANGE
        $fixture = createTicketIndexFixture(3);

        $otherDeveloper = User::factory()->create();
        $otherGame = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $otherAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $otherGame->id,
            'user_id' => $otherDeveloper->id,
        ]);
        Ticket::factory()->forAchievement($otherAchievement)->open()->create([
            'ticketable_author_id' => $otherDeveloper->id,
            'reporter_id' => User::factory()->create()->id,
        ]);

        $this->actingAs(User::factory()->create());

        // ACT
        $response = $this->getJson(route('api.ticket.index', ['scope' => 'game', 'game' => $fixture['game']->id]));

        // ASSERT
        $response->assertJsonPath('paginatedTickets.total', 3);
    });

    it('given scope game and a game id, the JSON has paginated tickets, every state count, and the facet counts', function () {
        // ARRANGE
        $fixture = createTicketIndexFixture(3);
        $this->actingAs(User::factory()->create());

        // ACT
        $response = $this->getJson(route('api.ticket.index', ['scope' => 'game', 'game' => $fixture['game']->id]));

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('paginatedTickets.total', 3);
        $response->assertJsonPath('paginatedTickets.perPage', 50);
        $response->assertJsonCount(3, 'paginatedTickets.items');
        $response->assertJsonStructure([
            'paginatedTickets' => ['currentPage', 'lastPage', 'perPage', 'total', 'unfilteredTotal', 'items', 'links'],
            'stateCounts' => ['unresolved', 'request', 'resolved', 'closed', 'quarantined', 'all'],
        ]);
        $response->assertJsonPath('stateCounts.unresolved', 3);
        $response->assertJsonPath('stateCounts.closed', 0);
        $response->assertJsonPath('stateCounts.all', 3);

        $response->assertJsonStructure(['facetCounts' => ['type', 'mode', 'emulator']]);
        $response->assertJsonPath('facetCounts.mode.all', 3);
        $response->assertJsonMissingPath('facetCounts.developer');

        $ids = collect($response->json('paginatedTickets.items'))->pluck('id')->all();
        $expectedIds = collect($fixture['tickets'])->sortByDesc('created_at')->pluck('id')->values()->all();
        expect($ids)->toEqual($expectedIds);
    });

    it('given the scope is unset, the global list is returned', function () {
        // ARRANGE
        createTicketIndexFixture(2);
        $this->actingAs(User::factory()->create());

        // ACT
        $response = $this->getJson(route('api.ticket.index'));

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('paginatedTickets.total', 2);
    });

    it('given the scoped user is banned, a standard user cannot filter to narrow the list to their tickets', function () {
        // ARRANGE
        $fixture = createTicketIndexFixture(3);
        $fixture['developer']->update(['banned_at' => Carbon::now()]);

        $this->actingAs(User::factory()->create());

        // ACT
        $response = $this->getJson(route('api.ticket.index', [
            'scope' => 'assignedTo',
            'user' => $fixture['developer']->id,
        ]));

        // ASSERT
        $response->assertNotFound();
    });

    it('given the scoped user is banned, a developer can still narrow the list to their tickets', function () {
        // ARRANGE
        $this->seed(RolesTableSeeder::class);

        $fixture = createTicketIndexFixture(3);
        $fixture['developer']->update(['banned_at' => Carbon::now()]);

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::DEVELOPER);
        $this->actingAs($viewer);

        // ACT
        $response = $this->getJson(route('api.ticket.index', [
            'scope' => 'assignedTo',
            'user' => $fixture['developer']->id,
        ]));

        // ASSERT
        $response->assertOk();
    });

    it('given the user is a developer, exposes signal data', function () {
        // ARRANGE
        $this->seed(RolesTableSeeder::class);
        $fixture = createTicketIndexFixture(1);
        PlayerSession::factory()->count(5)->create([
            'user_id' => fn () => User::factory()->create()->id,
            'game_id' => $fixture['game']->id,
            'rich_presence_updated_at' => Carbon::parse('2023-12-25'),
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::DEVELOPER);
        $this->actingAs($viewer);

        // ACT
        $response = $this->getJson(route('api.ticket.index', ['scope' => 'game', 'game' => $fixture['game']->id]));

        // ASSERT
        $response->assertOk();
        $response->assertJsonPath('paginatedTickets.items.0.signals.kinds', ['no-recent-unlocks']);
    });

    it('given the user has no developer role, does not expose signal data', function () {
        // ARRANGE
        $fixture = createTicketIndexFixture(1);
        $this->actingAs(User::factory()->create());

        // ACT
        $response = $this->getJson(route('api.ticket.index', ['scope' => 'game', 'game' => $fixture['game']->id]));

        // ASSERT
        $response->assertOk();
        $response->assertJsonCount(1, 'paginatedTickets.items');
        $response->assertJsonMissingPath('paginatedTickets.items.0.signals');
    });
});

describe('updateState', function () {
    beforeEach(function () {
        $this->seed(RolesTableSeeder::class);
    });

    it('given a user the policy rejects, responds forbidden and does not mutate the ticket', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => User::factory()->create()->id,
        ]);

        $stranger = User::factory()->create();
        $this->actingAs($stranger);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::ClosedMistaken->value,
        ]);

        // ASSERT
        $response->assertForbidden();

        $ticket->refresh();
        expect($ticket->state)->toEqual(TicketState::Open);
        expect($ticket->resolver_id)->toBeNull();
        expect($ticket->resolution)->toBeNull();
    });

    it('given a developer resolves an open ticket, records the state, resolver, and resolution and posts the Server comment', function () {
        // ARRANGE
        User::factory()->create(['username' => 'Server', 'display_name' => 'Server']);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create(['display_name' => 'Scott']);
        $developer->assignRole(Role::DEVELOPER);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $developer->id,
            'body' => 'Fixed in the latest revision.',
        ]);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::Resolved->value,
        ]);

        // ASSERT
        $response->assertOk();
        $response->assertJson(['message' => __('legacy.success.update')]);

        $ticket->refresh();
        expect($ticket->state)->toEqual(TicketState::Resolved);
        expect($ticket->resolver_id)->toEqual($developer->id);
        expect($ticket->resolution)->toEqual(TicketResolution::Fixed);
        expect($ticket->resolved_at)->not->toBeNull();

        $comment = Comment::where('commentable_type', CommentableType::Ticket)
            ->where('commentable_id', $ticket->id)
            ->where('user_id', '!=', $developer->id)
            ->sole();
        expect($comment->body)->toEqual('Ticket resolved as fixed by Scott.');
    });

    it('given a reporter closes their own ticket, does not send them an email notification', function () {
        // ARRANGE
        Notification::fake();

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $reporter = User::factory()->create(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => $reporter->id,
        ]);

        $this->actingAs($reporter);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::ClosedMistaken->value,
        ]);

        // ASSERT
        $response->assertOk();
        expect($ticket->refresh()->state)->toEqual(TicketState::Closed);
        Notification::assertNotSentTo($reporter, TicketStatusUpdatedNotification::class);
    });

    it('given a developer resolves or closes for another reason when only the reporter has commented, requires a comment', function (TicketAction $action) {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $reporter = User::factory()->create();
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => $reporter->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $reporter->id,
            'body' => 'It still does not unlock.',
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => $action->value,
        ]);

        // ASSERT
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['action' => 'Add a comment first.']);

        expect($ticket->refresh()->state)->toEqual(TicketState::Open);
    })->with([
        'resolve' => [TicketAction::Resolved],
        'close for another reason' => [TicketAction::ClosedOther],
    ]);

    it('given a developer approves a quarantined ticket, reopens it and notes the approval', function () {
        // ARRANGE
        User::factory()->create(['username' => 'Server', 'display_name' => 'Server']);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->quarantined()->create([
            'reporter_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create(['display_name' => 'Scott']);
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::Reopen->value,
        ]);

        // ASSERT
        $response->assertOk();

        expect($ticket->refresh()->state)->toEqual(TicketState::Open);

        $comment = Comment::where('commentable_type', CommentableType::Ticket)
            ->where('commentable_id', $ticket->id)
            ->sole();
        expect($comment->body)->toEqual('Ticket approved by Scott.');
    });

    it('given a developer reopens a resolved ticket, wipes the resolver and resolution', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $previousResolver = User::factory()->create();
        $ticket = Ticket::factory()->forAchievement($achievement)->resolved()->create([
            'reporter_id' => User::factory()->create()->id,
            'resolver_id' => $previousResolver->id,
            'resolution' => TicketResolution::Fixed,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::Reopen->value,
        ]);

        // ASSERT
        $response->assertOk();

        $ticket->refresh();
        expect($ticket->state)->toEqual(TicketState::Open);
        expect($ticket->resolver_id)->toBeNull();
        expect($ticket->resolution)->toBeNull();
        expect($ticket->resolved_at)->toBeNull();
    });

    it('given a reporter marks their own open ticket as mistaken, closes it', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $reporter = User::factory()->create();
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => $reporter->id,
        ]);

        $this->actingAs($reporter);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::ClosedMistaken->value,
        ]);

        // ASSERT
        $response->assertOk();

        $ticket->refresh();
        expect($ticket->state)->toEqual(TicketState::Closed);
        expect($ticket->resolution)->toEqual(TicketResolution::MistakenReport);
    });

    it('given a reporter tries to resolve their own ticket, responds forbidden', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $reporter = User::factory()->create();
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => $reporter->id,
        ]);

        $this->actingAs($reporter);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::Resolved->value,
        ]);

        // ASSERT
        $response->assertForbidden();

        expect($ticket->refresh()->state)->toEqual(TicketState::Open);
    });

    it('given an unknown action string, responds unprocessable', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => 'closed-eternally',
        ]);

        // ASSERT
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['action']);
    });

    it('given a developer who tries an action the ticket state does not allow, responds forbidden and leaves the ticket alone', function (TicketState $state, TicketAction $action) {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'reporter_id' => User::factory()->create()->id,
            'state' => $state,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => $action->value,
        ]);

        // ASSERT
        $response->assertForbidden();

        expect($ticket->refresh()->state)->toEqual($state);
        expect($achievement->refresh()->is_promoted)->toBeTrue();
    })->with([
        'reopen an open ticket' => [TicketState::Open, TicketAction::Reopen],
        'resolve a quarantined ticket' => [TicketState::Quarantined, TicketAction::Resolved],
        'demote a resolved ticket' => [TicketState::Resolved, TicketAction::Demoted],
    ]);

    it('given a developer who requests information when the latest comment is from someone else, responds forbidden', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => User::factory()->create()->id,
            'ticketable_author_id' => User::factory()->create()->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'I see the same problem.',
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::Request->value,
        ]);

        // ASSERT
        $response->assertForbidden();

        expect($ticket->refresh()->state)->toEqual(TicketState::Open);
    });

    it('given a developer who requests information after their own comment, moves the ticket to Request', function () {
        // ARRANGE
        User::factory()->create(['username' => 'Server', 'display_name' => 'Server']);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => User::factory()->create()->id,
            'ticketable_author_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $developer->id,
            'body' => 'Which level were you on?',
        ]);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::Request->value,
        ]);

        // ASSERT
        $response->assertOk();

        expect($ticket->refresh()->state)->toEqual(TicketState::Request);
    });

    it('given a developer who closes as unable to debug on a ticket whose emulator can debug triggers, responds forbidden', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $emulator = Emulator::factory()->create(['can_debug_triggers' => true]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'reporter_id' => User::factory()->create()->id,
            'emulator_id' => $emulator->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        $this->actingAs($developer);

        // ACT
        $response = $this->patchJson(route('api.ticket.state.update', $ticket), [
            'action' => TicketAction::UnableToDebug->value,
        ]);

        // ASSERT
        $response->assertForbidden();

        expect($ticket->refresh()->state)->toEqual(TicketState::Open);
    });
});
