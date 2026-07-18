<?php

declare(strict_types=1);

use App\Filament\Resources\GameScreenshotModerationResource;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows select roles to always access the screenshot moderation queue', function (string $role) {
    // ARRANGE
    Role::query()->firstOrCreate([
        'name' => $role,
        'guard_name' => 'web',
    ], [
        'display' => 1,
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    // ACT
    $response = $this->actingAs($user)->get(GameScreenshotModerationResource::getUrl('index'));

    // ASSERT
    $response->assertOk();
})->with([
    Role::ADMINISTRATOR,
    Role::MODERATOR,
    Role::GAME_EDITOR,
    Role::MEDIA_EDITOR,
]);

it('allows developers with promoted achievements to access the screenshot moderation queue', function () {
    // ARRANGE
    Role::query()->firstOrCreate([
        'name' => Role::DEVELOPER,
        'guard_name' => 'web',
    ], [
        'display' => 1,
    ]);

    $developer = User::factory()->create();
    $developer->assignRole(Role::DEVELOPER);

    $game = Game::factory()->create();
    Achievement::factory()
        ->for($game)
        ->for($developer, 'developer')
        ->promoted()
        ->create();

    // ACT
    $response = $this->actingAs($developer)->get(GameScreenshotModerationResource::getUrl('index'));

    // ASSERT
    $response->assertOk();
});

it('forbids developers without promoted achievements from accessing the screenshot moderation queue', function () {
    // ARRANGE
    Role::query()->firstOrCreate([
        'name' => Role::DEVELOPER,
        'guard_name' => 'web',
    ], [
        'display' => 1,
    ]);

    $developer = User::factory()->create();
    $developer->assignRole(Role::DEVELOPER);

    // ACT
    $response = $this->actingAs($developer)->get(GameScreenshotModerationResource::getUrl('index'));

    // ASSERT
    $response->assertForbidden();
});
