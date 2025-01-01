<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TriggerTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateDoesNotAllowUnverifiedUsers(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => null, // !!
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertStatus(403);
    }

    public function testCreateDoesNotAllowMutedUsers(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'muted_until' => Carbon::parse('2035-01-01'), // !!
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertStatus(403);
    }

    public function testCreateDoesNotAllowNewUsers(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now(), // !!
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertStatus(403);
    }

    public function testCreateDoesNotAllowUsersWhoHaveNeverPlayedTheGame(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'Created' => Carbon::now()->subWeeks(2),
            'UnreadMessageCount' => 0,
        ]);
        $this->actingAs($user);

        // !! there is no PlayerGame associated with this user and game

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertStatus(403);
    }

    public function testCreateRedirectsIfTheUserAlreadyHasAnOpenedTicket(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // !! they have an existing ticket !!
        $existingTicket = Ticket::factory()->create([
            'reporter_id' => $user->id,
            'AchievementID' => $achievement->id,
            'ReportState' => TicketState::Open, // !!
        ]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertRedirect(route('ticket.show', ['ticket' => $existingTicket->id]));
    }

    public function testCreateGivenThereAreNoHashesItRedirects(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $emulators = Emulator::factory()->count(3)->create();
        $system->emulators()->attach($emulators->pluck('id'));

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertRedirect(route('achievement.show', $achievement->id));
    }

    public function testCreateGivenThereAreNoEmulatorsItRedirects(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertRedirect(route('achievement.show', $achievement->id));
    }

    public function testCreateGivenUserHasNoPlayerSessionReturnsCorrectProps(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $emulators = Emulator::factory()->count(3)->create();
        $system->emulators()->attach($emulators->pluck('id'));

        Emulator::factory()->count(2)->create(); // verify not all emulators are put in props

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('achievement.id', $achievement->id)
            ->where('achievement.title', $achievement->title)

            ->where('achievement.game.id', $game->id)
            ->where('achievement.game.title', $game->title)

            ->where('achievement.game.system.id', $system->id)
            ->where('achievement.game.system.name', $system->name)

            ->has('emulators', 3)

            ->has('gameHashes', 5)
        );
    }

    public function testCreateGivenUserHasPlayerSessionReturnsCorrectProps(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $emulatorOne = Emulator::factory()->create(['name' => 'RALibRetro']);
        $emulatorTwo = Emulator::factory()->create(['name' => 'RetroArch']);
        $emulatorThree = Emulator::factory()->create(['name' => 'RAP64']);
        $system->emulators()->attach($emulatorOne->id);
        $system->emulators()->attach($emulatorTwo->id);
        $system->emulators()->attach($emulatorThree->id);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $playerSession = PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_hash_id' => $gameHashes->get(2)->id,
            'game_id' => $game->id,
            'hardcore' => true,
            'user_agent' => 'RetroArch/1.19.1 (Windows 8 x64 Build 9200 6.2) mupen64plus_next_libretro/2.6-Vulkan_5d2ac21',
            'duration' => 10,
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now(),
            'unlocked_hardcore_at' => now(),
            'player_session_id' => $playerSession->id,
        ]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('achievement.id', $achievement->id)
            ->where('achievement.title', $achievement->title)

            ->where('achievement.game.id', $game->id)
            ->where('achievement.game.title', $game->title)

            ->where('achievement.game.system.id', $system->id)
            ->where('achievement.game.system.name', $system->name)

            ->has('emulators', 3)

            ->has('gameHashes', 5)

            ->where('selectedEmulator', 'RetroArch')
            ->where('selectedGameHashId', $gameHashes->get(2)->id)
            ->where('selectedMode', 1)
            ->where('emulatorVersion', '1.19.1')
            ->where('emulatorCore', 'mupen64plus_next')
        );
    }

    public function testCreateGivenRecentHardcoreSessionButNoUnlockSetsHardcoreMode(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $emulatorOne = Emulator::factory()->create(['name' => 'RALibRetro']);
        $system->emulators()->attach($emulatorOne->id);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_hash_id' => $gameHashes->get(2)->id,
            'game_id' => $game->id,
            'hardcore' => true,
            'user_agent' => 'RetroArch/1.19.1 (Windows 8 x64 Build 9200 6.2) mupen64plus_next_libretro/2.6-Vulkan_5d2ac21',
            'duration' => 10,
        ]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('selectedMode', 1)
        );
    }

    public function testCreateGivenShortSessionDoesNotUseItForEmulatorInfo(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $emulatorOne = Emulator::factory()->create(['name' => 'RALibRetro']);
        $system->emulators()->attach($emulatorOne->id);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_hash_id' => $gameHashes->get(2)->id,
            'game_id' => $game->id,
            'hardcore' => true,
            'user_agent' => 'RetroArch/1.19.1 (Windows 8 x64 Build 9200 6.2) mupen64plus_next_libretro/2.6-Vulkan_5d2ac21',
            'duration' => 4, // !!!! less than 5 minutes
        ]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('selectedEmulator', null)
            ->where('selectedGameHashId', null)
            ->where('emulatorVersion', null)
            ->where('emulatorCore', null)
        );
    }

    public function testCreatePrefersUnlockSessionOverMoreRecentSession(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $gameHashes = GameHash::factory()->count(5)->create(['game_id' => $game->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $emulatorOne = Emulator::factory()->create(['name' => 'RALibRetro']);
        $system->emulators()->attach($emulatorOne->id);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // we'll have an old session with an achievement unlock
        $oldSession = PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'duration' => 10,
            'user_agent' => 'RetroArch/1.18.0',
            'created_at' => now()->subDays(2),
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'player_session_id' => $oldSession->id,
        ]);

        // we'll also have a new session that has no unlocks
        PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'duration' => 10,
            'user_agent' => 'RetroArch/1.19.0',
            'created_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->get(route('achievement.tickets.create', ['achievement' => $achievement]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('emulatorVersion', '1.18.0') // this is from the older session where there was an unlock
        );
    }
}
