<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    seed(RolesTableSeeder::class);
});

it('given the proper role, returns OK', function (string $role) {
    // ARRANGE
    $game = $this->seedGame();
    $player = User::factory()->create();

    $viewer = User::factory()->create();
    $viewer->assignRole($role);
    actingAs($viewer);

    // ACT
    $response = get(route('user.game.activity.show', ['user' => $player, 'game' => $game]));

    // ASSERT
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('user/[user]/game/[game]/activity'));
})->with([
    'administrator' => Role::ADMINISTRATOR,
    'moderator' => Role::MODERATOR,
    'cheat investigator' => Role::CHEAT_INVESTIGATOR,
    'quality assurance' => Role::QUALITY_ASSURANCE,
]);

it('given a ticket viewer and a player who reported an achievement on the game, returns OK', function (string $role) {
    // ARRANGE
    $game = $this->seedGame();
    $player = User::factory()->create();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    Ticket::factory()->forAchievement($achievement)->closed()->create(['reporter_id' => $player->id]);

    $viewer = User::factory()->create();
    $viewer->assignRole($role);
    actingAs($viewer);

    // ACT
    $response = get(route('user.game.activity.show', ['user' => $player, 'game' => $game]));

    // ASSERT
    $response->assertOk();
})->with([
    'developer' => Role::DEVELOPER,
    'junior developer' => Role::DEVELOPER_JUNIOR,
    'ticket manager' => Role::TICKET_MANAGER,
    'manual unlocker' => Role::MANUAL_UNLOCKER,
]);

it('given a developer and a player who reported a leaderboard on the game, returns OK', function () {
    // ARRANGE
    $game = $this->seedGame();
    $player = User::factory()->create();
    $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
    Ticket::factory()->forLeaderboard($leaderboard)->create(['reporter_id' => $player->id]);

    $developer = User::factory()->create();
    $developer->assignRole(Role::DEVELOPER);
    actingAs($developer);

    // ACT
    $response = get(route('user.game.activity.show', ['user' => $player, 'game' => $game]));

    // ASSERT
    $response->assertOk();
});

it('given a developer and a player with no ticket on the game, fails', function () {
    // ARRANGE
    $game = $this->seedGame();
    $player = User::factory()->create();

    $developer = User::factory()->create();
    $developer->assignRole(Role::DEVELOPER);
    actingAs($developer);

    // ACT
    $response = get(route('user.game.activity.show', ['user' => $player, 'game' => $game]));

    // ASSERT
    $response->assertForbidden();
});

it('given a developer and a player whose only ticket is on a different game, fails', function () {
    // ARRANGE
    $game = $this->seedGame();
    $otherGame = $this->seedGame();
    $player = User::factory()->create();
    $otherAchievement = Achievement::factory()->promoted()->create(['game_id' => $otherGame->id]);
    Ticket::factory()->forAchievement($otherAchievement)->create(['reporter_id' => $player->id]);

    $developer = User::factory()->create();
    $developer->assignRole(Role::DEVELOPER);
    actingAs($developer);

    // ACT
    $response = get(route('user.game.activity.show', ['user' => $player, 'game' => $game]));

    // ASSERT
    $response->assertForbidden();
});
