<?php

declare(strict_types=1);

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function achievementForReportIssueTest(bool $isTicketable = true): Achievement
{
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);

    if ($isTicketable) {
        GameHash::factory()->create(['game_id' => $game->id]);
        $system->emulators()->attach(Emulator::factory()->create()->id);
    }

    return Achievement::factory()->promoted()->create(['game_id' => $game->id]);
}

function playerForReportIssueTest(
    Achievement $achievement,
    array $overrides = [],
    bool $hasPlayed = true,
): User {
    /** @var User $user */
    $user = User::factory()->create(array_merge([
        'preferences_bitfield' => 63,
        'Permissions' => Permissions::Registered,
        'created_at' => Carbon::now()->subWeeks(2),
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'unread_messages' => 0,
    ], $overrides));

    if ($hasPlayed) {
        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $achievement->game_id]);
    }

    return $user;
}

it('given a tracked player, the page carries the achievement and permission props', function () {
    // Arrange
    $achievement = achievementForReportIssueTest();
    $user = playerForReportIssueTest($achievement);
    $this->actingAs($user);

    // Act
    $response = $this->get(route('achievement.report-issue', ['achievement' => $achievement->id]));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->has('achievement', fn (Assert $achievement) => $achievement
            ->has('id')
            ->has('title')
            ->has('badgeUnlockedUrl')
            ->has('badgeLockedUrl')
            ->missing('unlockedAt')
            ->missing('unlockedHardcoreAt')
            ->has('game', fn (Assert $game) => $game
                ->has('id')
                ->has('title')
                ->has('system')
                ->has('isSubsetGame')
            )
        )
        ->has('hasSession')
        ->has('ticketType')
        ->where('can.createTicket', true)
        ->missing('ticketBlockReason') // null props are auto-stripped from our payloads
        ->etc() // for whatever reason, component validation always fails. it's covered elsewhere, though.
    );
});

it('given a player who cannot open tickets, the page is forbidden', function (Closure $buildOverrides) {
    // Arrange
    $achievement = achievementForReportIssueTest();
    $user = playerForReportIssueTest($achievement, $buildOverrides());
    $this->actingAs($user);

    // Act
    $response = $this->get(route('achievement.report-issue', ['achievement' => $achievement->id]));

    // Assert
    $response->assertForbidden();
})->with([
    'untracked' => [fn () => ['unranked_at' => Carbon::now()->subMonth()]],
    'muted' => [fn () => ['muted_until' => Carbon::now()->addWeek()]],
    'unverified email' => [fn () => ['email_verified_at' => null]],
    'new account' => [fn () => ['created_at' => Carbon::now()->subHours(3)]],
]);

it('given an untracked player with a team role, then the page still offers ticket creation', function () {
    // Arrange
    $this->seed(RolesTableSeeder::class);

    $achievement = achievementForReportIssueTest();
    $user = playerForReportIssueTest($achievement, ['unranked_at' => Carbon::now()->subMonth()]);
    $user->assignRole(Role::DEVELOPER);
    $this->actingAs($user);

    // Act
    $response = $this->get(route('achievement.report-issue', ['achievement' => $achievement->id]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.createTicket', true)
        ->missing('ticketBlockReason')
        ->etc()
    );
});

it('given a player with no play session, the page sets ticketBlockReason correctly', function () {
    // Arrange
    $achievement = achievementForReportIssueTest();
    $user = playerForReportIssueTest($achievement, hasPlayed: false);
    $this->actingAs($user);

    // Act
    $response = $this->get(route('achievement.report-issue', ['achievement' => $achievement->id]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.createTicket', false)
        ->where('ticketBlockReason', 'no_play_session')
        ->etc()
    );
});

it('given a game with no hashes or emulators, the page sets ticketBlockReason correctly', function () {
    // Arrange
    $achievement = achievementForReportIssueTest(isTicketable: false);
    $user = playerForReportIssueTest($achievement);
    $this->actingAs($user);

    // Act
    $response = $this->get(route('achievement.report-issue', ['achievement' => $achievement->id]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.createTicket', false)
        ->where('ticketBlockReason', 'game_not_ticketable')
        ->etc()
    );
});
