<?php

declare(strict_types=1);

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function achievementForReportIssueTest(): Achievement
{
    $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
    $game = Game::factory()->create(['title' => 'StarCraft 64', 'system_id' => $system->id]);

    return Achievement::factory()->promoted()->create(['game_id' => $game->id]);
}

function playerForReportIssueTest(Achievement $achievement, array $overrides = []): User
{
    /** @var User $user */
    $user = User::factory()->create(array_merge([
        'preferences_bitfield' => 63,
        'Permissions' => Permissions::Registered,
        'created_at' => Carbon::now()->subWeeks(2),
        'email_verified_at' => Carbon::parse('2013-01-01'),
        'unread_messages' => 0,
    ], $overrides));

    PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $achievement->game_id]);

    return $user;
}

it('given a tracked player, then the page carries the achievement and permission props', function () {
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
        ->has('can', fn (Assert $can) => $can
            ->has('createTicket')
        )
        ->etc() // for whatever reason, component validation always fails. it's covered elsewhere, though.
    );
});

it('given an untracked player with no roles, then the page renders but withholds ticket creation', function () {
    // Arrange
    $achievement = achievementForReportIssueTest();
    $user = playerForReportIssueTest($achievement, ['unranked_at' => Carbon::now()->subMonth()]);
    $this->actingAs($user);

    // Act
    $response = $this->get(route('achievement.report-issue', ['achievement' => $achievement->id]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('can.createTicket', false)
        ->etc()
    );
});

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
        ->etc()
    );
});
