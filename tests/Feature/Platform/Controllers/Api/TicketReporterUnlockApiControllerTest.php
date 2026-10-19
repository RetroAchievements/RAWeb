<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\PlayerAchievement;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function makeReporterUnlockTestGame(): Game
{
    $system = System::factory()->create();

    return Game::factory()->create(['system_id' => $system->id]);
}

function makeReporterUnlockAwarder(): User
{
    seed(RolesTableSeeder::class);

    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(Role::MANUAL_UNLOCKER);

    return $user;
}

it('given a guest, is unauthorized', function () {
    // ARRANGE
    $game = makeReporterUnlockTestGame();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'reporter_id' => User::factory()->create()->id,
    ]);

    // ACT
    $response = $this->postJson(route('api.ticket.reporter-unlock.store', ['ticket' => $ticket]), [
        'isHardcore' => false,
    ]);

    // ASSERT
    $response->assertUnauthorized();
});

it('given a user without manual award permission, is forbidden and does not trigger a manual unlock', function () {
    // ARRANGE
    $game = makeReporterUnlockTestGame();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    $reporter = User::factory()->create();
    $ticket = Ticket::factory()->forAchievement($achievement)->create(['reporter_id' => $reporter->id]);

    $this->actingAs(User::factory()->create());

    // ACT
    $response = $this->postJson(route('api.ticket.reporter-unlock.store', ['ticket' => $ticket]), [
        'isHardcore' => false,
    ]);

    // ASSERT
    $response->assertForbidden();
    expect(PlayerAchievement::where('user_id', $reporter->id)->exists())->toBeFalse();
});

it('given a casual award is requested, grants a manual casual unlock', function () {
    // ARRANGE
    $game = makeReporterUnlockTestGame();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    $reporter = User::factory()->create();
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'reporter_id' => $reporter->id,
        'hardcore' => true,
    ]);

    $awarder = makeReporterUnlockAwarder();
    $this->actingAs($awarder);

    // ACT
    $response = $this->postJson(route('api.ticket.reporter-unlock.store', ['ticket' => $ticket]), [
        'isHardcore' => false,
    ]);

    // ASSERT
    $response->assertNoContent();

    $unlock = PlayerAchievement::where('user_id', $reporter->id)
        ->where('achievement_id', $achievement->id)
        ->first();
    expect($unlock)->not->toBeNull();
    expect($unlock->unlocker_id)->toEqual($awarder->id);
    expect($unlock->unlocked_at)->not->toBeNull();
    expect($unlock->unlocked_hardcore_at)->toBeNull();
});

it('given a hardcore award is requested on a hardcore session ticket, grants a manual hardcore unlock', function () {
    // ARRANGE
    $game = makeReporterUnlockTestGame();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    $reporter = User::factory()->create();
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'reporter_id' => $reporter->id,
        'hardcore' => true,
    ]);

    $awarder = makeReporterUnlockAwarder();
    $this->actingAs($awarder);

    // ACT
    $response = $this->postJson(route('api.ticket.reporter-unlock.store', ['ticket' => $ticket]), [
        'isHardcore' => true,
    ]);

    // ASSERT
    $response->assertNoContent();

    $unlock = PlayerAchievement::where('user_id', $reporter->id)
        ->where('achievement_id', $achievement->id)
        ->first();
    expect($unlock->unlocker_id)->toEqual($awarder->id);
    expect($unlock->unlocked_hardcore_at)->not->toBeNull();
});

it('given a hardcore award on a casual session ticket, responds with unprocessable and does not grant any manual unlock', function () {
    // ARRANGE
    $game = makeReporterUnlockTestGame();
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
    $reporter = User::factory()->create();
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'reporter_id' => $reporter->id,
        'hardcore' => false, // Casual report.
    ]);

    $this->actingAs(makeReporterUnlockAwarder());

    // ACT
    $response = $this->postJson(route('api.ticket.reporter-unlock.store', ['ticket' => $ticket]), [
        'isHardcore' => true,
    ]);

    // ASSERT
    $response->assertUnprocessable();
    expect(PlayerAchievement::where('user_id', $reporter->id)->exists())->toBeFalse();
});

it('given a leaderboard ticket, does not grant any manual unlock', function () {
    // ARRANGE
    $game = makeReporterUnlockTestGame();
    $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
    $ticket = Ticket::factory()->forLeaderboard($leaderboard)->create([
        'reporter_id' => User::factory()->create()->id,
    ]);

    $this->actingAs(makeReporterUnlockAwarder());

    // ACT
    $response = $this->postJson(route('api.ticket.reporter-unlock.store', ['ticket' => $ticket]), [
        'isHardcore' => false,
    ]);

    // ASSERT
    $response->assertNotFound();
});
