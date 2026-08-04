<?php

declare(strict_types=1);

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\EmulatorCoreRestriction;
use App\Models\EmulatorUserAgent;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
