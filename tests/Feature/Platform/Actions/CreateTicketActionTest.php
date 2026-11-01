<?php

declare(strict_types=1);

use App\Community\Enums\TicketSignalVerdict;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\ClientSupportLevel;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\EmulatorCoreRestriction;
use App\Models\EmulatorUserAgent;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\System;
use App\Models\Trigger;
use App\Models\User;
use App\Notifications\Ticket\TicketCreatedNotification;
use App\Platform\Actions\CreateTicketAction;
use App\Platform\Data\StoreTicketData;
use App\Platform\Enums\TriggerableType;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->system = System::factory()->create(['name' => 'PlayStation 2', 'active' => true]);
    $this->game = Game::factory()->create(['title' => 'Resident Evil 4', 'system_id' => $this->system->id]);
    $this->gameHash = GameHash::factory()->create(['game_id' => $this->game->id]);
    $this->developer = User::factory()->create();
    $this->achievement = Achievement::factory()->promoted()->create([
        'game_id' => $this->game->id,
        'user_id' => $this->developer->id,
    ]);
    $this->reporter = User::factory()->create();
});

function ticketData(array $overrides = []): StoreTicketData
{
    /** @var object{achievement: Achievement, gameHash: GameHash} $context */
    $context = test();

    return new StoreTicketData(
        ticketable: $overrides['ticketable'] ?? $context->achievement,
        mode: $overrides['mode'] ?? 'hardcore',
        issue: $overrides['issue'] ?? TicketType::DidNotTrigger,
        description: $overrides['description'] ?? 'The achievement never unlocked.',
        emulator: $overrides['emulator'] ?? 'PCSX2',
        emulatorVersion: $overrides['emulatorVersion'] ?? '2.0.0',
        core: $overrides['core'] ?? null,
        gameHash: $overrides['gameHash'] ?? $context->gameHash,
        extra: $overrides['extra'] ?? null,
    );
}

it('given the reporter only has a session on the parent game and provides an emulator that cannot debug triggers, it quarantines the subset ticket', function () {
    // Arrange
    $subset = Game::factory()->create([
        'title' => 'Resident Evil 4 [Subset - Bonus]',
        'system_id' => $this->system->id,
    ]);
    $subsetAchievement = Achievement::factory()->promoted()->create([
        'game_id' => $subset->id,
        'user_id' => $this->developer->id,
    ]);

    $emulator = Emulator::factory()->create(['name' => 'AetherSX2', 'can_debug_triggers' => false]);
    EmulatorUserAgent::create(['emulator_id' => $emulator->id, 'client' => 'AetherSX2']);

    PlayerSession::factory()->create([
        'user_id' => $this->reporter->id,
        'game_id' => $this->game->id, // the base game, not the subset
        'game_hash_id' => $this->gameHash->id,
        'user_agent' => 'AetherSX2/1.5-4248 (Android)',
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['ticketable' => $subsetAchievement, 'emulator' => 'AetherSX2']),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
    expect(PlayerSession::where('game_id', $subset->id)->exists())->toEqual(false);
});

it('given the reporter provides an emulator developers cannot work with and has no session at all, it quarantines the ticket', function (bool $canDebugTriggers, bool $softcoreOnly) {
    // Arrange
    Emulator::factory()->create([
        'name' => 'ReportedEmulator',
        'can_debug_triggers' => $canDebugTriggers,
        'softcore_only' => $softcoreOnly,
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['emulator' => 'ReportedEmulator']),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
})->with([
    'cannot debug triggers' => [false, false],
    'casual only' => [true, true],
]);

it('given the reporter provides a restricted core on a fully supported emulator, it quarantines the ticket', function (?string $minimumVersion) {
    // Arrange
    Emulator::factory()->create(['name' => 'RetroArch', 'can_debug_triggers' => true]);
    EmulatorCoreRestriction::create([
        'core_name' => 'doublecherrygb_libretro',
        'support_level' => ClientSupportLevel::Warned,
        'minimum_version' => $minimumVersion,
        'notes' => 'known issues',
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['emulator' => 'RetroArch', 'core' => 'doublecherrygb_libretro']),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
})->with([
    'no minimum version' => [null],
    'a minimum version the form cannot satisfy' => ['9.9.9'],
]);

it('given a fully supported emulator and no session, it leaves the ticket open', function (?string $core) {
    // Arrange
    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true, 'softcore_only' => false]);

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(['core' => $core]), $this->reporter);

    // Assert
    expect($ticket->state)->toEqual(TicketState::Open);
})->with([
    'an unrestricted core' => ['some_unregistered_libretro'],
    'no core' => [null],
]);

it('given a standalone game, the ticket state is set to open even though the client fails our emulator checks', function (string $emulatorName) {
    // Arrange
    $standalonesSystem = System::factory()->create(['id' => System::Standalones]);
    $standaloneGame = Game::factory()->create(['title' => 'Undertale', 'system_id' => $standalonesSystem->id]);
    $standaloneAchievement = Achievement::factory()->promoted()->create([
        'game_id' => $standaloneGame->id,
        'user_id' => $this->developer->id,
    ]);

    Emulator::factory()->create(['name' => 'Undertale', 'can_debug_triggers' => false]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['ticketable' => $standaloneAchievement, 'emulator' => $emulatorName]),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Open);
})->with([
    'a client that cannot debug triggers' => ['Undertale'],
    'an emulator name that matches no record' => ['Other'],
]);

it('given a game set to quarantine new tickets, it quarantines the ticket even from a fully supported emulator', function () {
    // Arrange
    $this->game->update(['is_ticket_quarantine_enabled' => true]);

    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true, 'softcore_only' => false]);

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(), $this->reporter);

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
});

it('given a standalone subset whose base game is set to quarantine new tickets, it quarantines subset tickets too', function () {
    // Arrange
    $standalonesSystem = System::factory()->create(['id' => System::Standalones]);
    $baseGame = Game::factory()->create([
        'title' => 'Final Fantasy XI',
        'system_id' => $standalonesSystem->id,
        'is_ticket_quarantine_enabled' => true,
    ]);
    $subset = Game::factory()->create([
        'title' => 'Final Fantasy XI [Subset - Hero of Nations]',
        'system_id' => $standalonesSystem->id,
        'parent_game_id' => $baseGame->id,
    ]);
    $subsetAchievement = Achievement::factory()->promoted()->create([
        'game_id' => $subset->id,
        'user_id' => $this->developer->id,
    ]);

    Emulator::factory()->create(['name' => 'HorizonXI', 'can_debug_triggers' => false]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['ticketable' => $subsetAchievement, 'emulator' => 'HorizonXI']),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
});

it('given an emulator name that matches no record, it quarantines the ticket and keeps the name in the body', function () {
    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['emulator' => 'Other (please specify in description)']),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
    expect($ticket->emulator_id)->toEqual(null);
    expect($ticket->emulator_version)->toEqual(null);
    expect($ticket->body)->toContain('Emulator: Other (please specify in description)');
});

it('given an unresolvable emulator but a fully supported client in the session, it still quarantines the ticket', function () {
    // Arrange
    $sessionEmulator = Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true]);
    EmulatorUserAgent::create(['emulator_id' => $sessionEmulator->id, 'client' => 'PCSX2']);

    PlayerSession::factory()->create([
        'user_id' => $this->reporter->id,
        'game_id' => $this->game->id,
        'game_hash_id' => $this->gameHash->id,
        'user_agent' => 'PCSX2 v2.0.0 (Windows)',
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['emulator' => 'Other (please specify in description)']),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
});

it('given ticket creation, it persists the emulator, version, core, and game hash', function () {
    // Arrange
    $emulator = Emulator::factory()->create(['name' => 'RetroArch', 'can_debug_triggers' => true]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['emulator' => 'RetroArch', 'emulatorVersion' => '1.19.1', 'core' => 'gambatte_libretro']),
        $this->reporter,
    );

    // Assert
    $ticket->refresh();

    expect($ticket->emulator_id)->toEqual($emulator->id);
    expect($ticket->emulator_version)->toEqual('1.19.1');
    expect($ticket->emulator_core)->toEqual('gambatte_libretro');
    expect($ticket->game_hash_id)->toEqual($this->gameHash->id);
});

it('given a supported emulator on the form but a casual-only client in the session, it still quarantines the ticket', function () {
    // Arrange
    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true]);

    $sessionEmulator = Emulator::factory()->create([
        'name' => 'gopher64',
        'softcore_only' => true,
        'can_debug_triggers' => true,
    ]);
    EmulatorUserAgent::create(['emulator_id' => $sessionEmulator->id, 'client' => 'gopher64']);

    PlayerSession::factory()->create([
        'user_id' => $this->reporter->id,
        'game_id' => $this->game->id,
        'game_hash_id' => $this->gameHash->id,
        'user_agent' => 'gopher64/1.15.0 (Windows)',
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(), $this->reporter);

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
});

it('given a supported emulator on the form but a session client that cannot debug triggers, it still quarantines the ticket', function () {
    // Arrange
    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true]);

    $sessionEmulator = Emulator::factory()->create(['name' => 'AetherSX2', 'can_debug_triggers' => false]);
    EmulatorUserAgent::create(['emulator_id' => $sessionEmulator->id, 'client' => 'AetherSX2']);

    PlayerSession::factory()->create([
        'user_id' => $this->reporter->id,
        'game_id' => $this->game->id,
        'game_hash_id' => $this->gameHash->id,
        'user_agent' => 'AetherSX2/1.5-4248 (Android)',
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(), $this->reporter);

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
});

it('given no core on the form but a restricted core in the session user agent, it still quarantines the ticket', function () {
    // Arrange
    $sessionEmulator = Emulator::factory()->create(['name' => 'RetroArch', 'can_debug_triggers' => true]);
    EmulatorUserAgent::create(['emulator_id' => $sessionEmulator->id, 'client' => 'RetroArch']);

    EmulatorCoreRestriction::create([
        'core_name' => 'doublecherrygb_libretro',
        'support_level' => ClientSupportLevel::Warned,
        'notes' => 'known issues',
    ]);

    PlayerSession::factory()->create([
        'user_id' => $this->reporter->id,
        'game_id' => $this->game->id,
        'game_hash_id' => $this->gameHash->id,
        'user_agent' => 'RetroArch/1.19.1 (Windows) doublecherrygb_libretro/5d2ac21',
    ]);

    // Act
    $ticket = (new CreateTicketAction())->execute(
        ticketData(['emulator' => 'RetroArch', 'core' => null]),
        $this->reporter,
    );

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
});

it('given a leaderboard as the ticketable, it refuses to create the ticket', function () {
    // Arrange
    $leaderboard = Leaderboard::factory()->create(['game_id' => $this->game->id]);

    // Act
    $execute = fn () => (new CreateTicketAction())->execute(
        ticketData(['ticketable' => $leaderboard]),
        $this->reporter,
    );

    // Assert
    expect($execute)->toThrow(InvalidArgumentException::class);
});

it('given a quarantined ticket, it sends no notifications', function () {
    // Arrange
    $this->seed(RolesTableSeeder::class);
    $this->developer->assignRole(Role::DEVELOPER);
    $this->developer->update(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);

    Emulator::factory()->create(['name' => 'AetherSX2', 'can_debug_triggers' => false]);

    // Act
    (new CreateTicketAction())->execute(ticketData(['emulator' => 'AetherSX2']), $this->reporter);

    // Assert
    Notification::assertNothingSent();
});

it('given an open ticket is created, the achievement maintainer is notified', function () {
    // Arrange
    $this->seed(RolesTableSeeder::class);
    $this->developer->assignRole(Role::DEVELOPER);
    $this->developer->update(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);

    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true]);

    // Act
    (new CreateTicketAction())->execute(ticketData(), $this->reporter);

    // Assert
    Notification::assertSentTo($this->developer, TicketCreatedNotification::class);
});

it('given the achievement has a current trigger, the ticket records that trigger id', function () {
    // Arrange
    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true]);
    $trigger = Trigger::factory()->create([
        'triggerable_type' => TriggerableType::Achievement,
        'triggerable_id' => $this->achievement->id,
        'version' => 2,
    ]);
    $this->achievement->update(['trigger_id' => $trigger->id]);

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(), $this->reporter->refresh());

    // Assert
    expect($ticket->trigger_id)->toEqual($trigger->id);
    expect($ticket->state)->toEqual(TicketState::Open);
});

it('given a quarantined ticket, still records the trigger id', function () {
    // Arrange
    Emulator::factory()->create(['name' => 'AetherSX2', 'can_debug_triggers' => false]);
    $trigger = Trigger::factory()->create([
        'triggerable_type' => TriggerableType::Achievement,
        'triggerable_id' => $this->achievement->id,
        'version' => 1,
    ]);
    $this->achievement->update(['trigger_id' => $trigger->id]);

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(['emulator' => 'AetherSX2']), $this->reporter);

    // Assert
    expect($ticket->state)->toEqual(TicketState::Quarantined);
    expect($ticket->trigger_id)->toEqual($trigger->id);
});

it('given no recent unlocks while other players played the game, writes the correct signal verdict on the new ticket', function () {
    // Arrange
    Emulator::factory()->create(['name' => 'PCSX2', 'can_debug_triggers' => true]);
    foreach (User::factory()->count(5)->create() as $player) {
        PlayerSession::factory()->create([
            'user_id' => $player->id,
            'game_id' => $this->game->id,
            'rich_presence_updated_at' => now()->subDays(3),
        ]);
    }

    // Act
    $ticket = (new CreateTicketAction())->execute(ticketData(), $this->reporter);

    // Assert
    expect($ticket->fresh()->signal_verdict)->toEqual(TicketSignalVerdict::ProbablyDefect);
});
