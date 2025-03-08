<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers\Api;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriggerTicketApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreAbortsIfUserIsMuted(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'Created' => Carbon::now()->subWeeks(2),
            'muted_until' => Carbon::parse('2035-01-01'), // !!
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->postJson(route('api.ticket.store'), [
            'ticketableModel' => 'achievement',
            'ticketableId' => $achievement->id,
            'mode' => 'hardcore',
            'issue' => TicketType::TriggeredAtWrongTime,
            'description' => 'Test description',
            'emulator' => 'RetroArch',
            'emulatorVersion' => '1.16.0',
            'core' => 'mupen64plus',
            'gameHashId' => $gameHash->id,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function testStoreAbortsIfUserIsNew(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'Created' => Carbon::now(), // !!
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->postJson(route('api.ticket.store'), [
            'ticketableModel' => 'achievement',
            'ticketableId' => $achievement->id,
            'mode' => 'hardcore',
            'issue' => TicketType::TriggeredAtWrongTime,
            'description' => 'Test description',
            'emulator' => 'RetroArch',
            'emulatorVersion' => '1.16.0',
            'core' => 'mupen64plus',
            'gameHashId' => $gameHash->id,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function testStoreCreatesTicketSuccessfully(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $developer = User::factory()->create();
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'user_id' => $developer->id]);
        $emulator = Emulator::factory()->create(['name' => 'RetroArch']);

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'Created' => Carbon::now()->subWeeks(2),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->postJson(route('api.ticket.store'), [
            'ticketableModel' => 'achievement',
            'ticketableId' => $achievement->id,
            'mode' => 'hardcore',
            'issue' => TicketType::TriggeredAtWrongTime,
            'description' => 'Test description',
            'emulator' => 'RetroArch',
            'emulatorVersion' => '1.16.0',
            'core' => 'mupen64plus',
            'gameHashId' => $gameHash->id,
        ]);

        // Assert
        $response->assertOk();

        $this->assertDatabaseHas('Ticket', [
            'ticketable_author_id' => $developer->id,
            'AchievementID' => $achievement->id,
            'reporter_id' => $user->id,
            'ReportType' => TicketType::TriggeredAtWrongTime,
            'hardcore' => 1,
            'emulator_id' => $emulator->id,
            'emulator_version' => '1.16.0',
            'emulator_core' => 'mupen64plus',
            'game_hash_id' => $gameHash->id,
            "ReportNotes" => "Test description", // emulator data is not captured when an emulator record is found
        ]);
    }

    public function testStoreFormatsTicketNotesCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $developer = User::factory()->create();
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'user_id' => $developer->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'Created' => Carbon::now()->subWeeks(2),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->postJson(route('api.ticket.store'), [
            'ticketableModel' => 'achievement',
            'ticketableId' => $achievement->id,
            'mode' => 'hardcore',
            'issue' => TicketType::TriggeredAtWrongTime,
            'description' => 'Test description',
            'emulator' => 'RetroArch',
            'emulatorVersion' => '1.16.0',
            'core' => 'mupen64plus',
            'gameHashId' => $gameHash->id,
            'extra' => 'eyJ0cmlnZ2VyUmljaFByZXNlbmNlIjoi8J+Qukxpbmsg8J+Xuu+4j0RlYXRoIE1vdW50YWluIOKdpO+4jzMvMyDwn5GlMS80IPCfp78wLzQg8J+RuzAvNjAg8J+QnDAvMjQg8J+SgDUg8J+VmTEyOjAwIEFN8J+MmSJ9',
        ]);

        // Assert
        $response->assertOk();

        $this->assertDatabaseHas('Ticket', [
            "ReportNotes" => "Test description\n\n" .
                "Rich Presence at time of trigger:\n" .
                "🐺Link 🗺️Death Mountain ❤️3/3 👥1/4 🧿0/4 👻0/60 🐜0/24 💀5 🕙12:00 AM🌙\n" .
                "Emulator: RetroArch (mupen64plus)\n" .
                "Emulator Version: 1.16.0",
        ]);
    }

    public function testStoreDoesNotCreateDuplicateTickets(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $developer = User::factory()->create();
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'user_id' => $developer->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'Created' => Carbon::now()->subWeeks(2),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // !!! the user already has a ticket open !!!
        $existingTicket = Ticket::factory()->create([
            'reporter_id' => $user->id,
            'AchievementID' => $achievement->id,
            'ReportState' => TicketState::Open,
        ]);

        // Act
        $response = $this->postJson(route('api.ticket.store'), [
            'ticketableModel' => 'achievement',
            'ticketableId' => $achievement->id,
            'mode' => 'hardcore',
            'issue' => 1,
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
    }

    public function testStoreProperlyHandlesEmulatorsWithoutCore(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id]);
        $developer = User::factory()->create();
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'user_id' => $developer->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'Created' => Carbon::now()->subWeeks(2),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->postJson(route('api.ticket.store'), [
            'ticketableModel' => 'achievement',
            'ticketableId' => $achievement->id,
            'mode' => 'hardcore',
            'issue' => 1,
            'description' => 'Test description',
            'emulator' => 'RAP64',
            'emulatorVersion' => '1.9.0',
            // 'core' => 'genesis_plus_gx', !! commented this out on purpose, the user isn't sending it on submit
            'gameHashId' => $gameHash->id,
        ]);

        // Assert
        $response->assertOk();

        $this->assertDatabaseHas('Ticket', [
            'ReportNotes' => "Test description\n\n" .
                "Emulator: RAP64\n" .
                "Emulator Version: 1.9.0",
        ]);
    }
}
