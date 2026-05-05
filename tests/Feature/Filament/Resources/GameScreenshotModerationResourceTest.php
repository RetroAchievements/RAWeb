<?php

declare(strict_types=1);

use App\Filament\Resources\GameScreenshotModerationResource;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('warns when approving a primary screenshot would create mixed valid primary resolutions', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 256, 'height' => 224],
            ['width' => 320, 'height' => 240],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 256,
            'height' => 224,
        ]);

        return GameScreenshot::factory()->for($game)->completion()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->not->toBeNull();
    expect($warning['message'])->toEqual('Primary screenshots will no longer match in size.');
    expect($warning['resolutions'])->toEqual([
        'Completion' => '320x240',
        'Title' => '256x224',
    ]);
});

it('does not warn when the only differing primary has an invalid legacy resolution', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 256, 'height' => 224],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 320,
            'height' => 240,
        ]);

        return GameScreenshot::factory()->for($game)->completion()->pending()->create([
            'width' => 256,
            'height' => 224,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->toBeNull();
});

it('does not warn for unrestricted systems', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => null,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 256,
            'height' => 224,
        ]);

        return GameScreenshot::factory()->for($game)->completion()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->toBeNull();
});

it('does not warn for ingame approvals that do not change the primary screenshot', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 256, 'height' => 224],
            ['width' => 320, 'height' => 240],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 256,
            'height' => 224,
        ]);
        GameScreenshot::factory()->for($game)->ingame()->primary()->create([
            'width' => 256,
            'height' => 224,
        ]);

        return GameScreenshot::factory()->for($game)->ingame()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->toBeNull();
});

it('does not warn when all valid primary screenshots match in size', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 320, 'height' => 240],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 320,
            'height' => 240,
        ]);
        GameScreenshot::factory()->for($game)->ingame()->primary()->create([
            'width' => 320,
            'height' => 240,
        ]);

        return GameScreenshot::factory()->for($game)->completion()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->toBeNull();
});

it('warns when any other valid primary screenshot has a mismatched resolution', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 320, 'height' => 240],
            ['width' => 320, 'height' => 224],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->ingame()->primary()->create([
            'width' => 320,
            'height' => 240,
        ]);
        GameScreenshot::factory()->for($game)->completion()->primary()->create([
            'width' => 320,
            'height' => 224,
        ]);

        return GameScreenshot::factory()->for($game)->title()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->not->toBeNull();
    expect($warning['resolutions'])->toEqual([
        'Title' => '320x240',
        'In-game' => '320x240',
        'Completion' => '320x224',
    ]);
});

it('does not warn when the incoming screenshot has an invalid resolution', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 256, 'height' => 224],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 256,
            'height' => 224,
        ]);

        return GameScreenshot::factory()->for($game)->completion()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->toBeNull();
});

it('does not compare against the current primary of the same type being replaced', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [
            ['width' => 256, 'height' => 224],
            ['width' => 320, 'height' => 240],
        ],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);

    $pending = GameScreenshot::withoutEvents(function () use ($game): GameScreenshot {
        GameScreenshot::factory()->for($game)->title()->primary()->create([
            'width' => 256,
            'height' => 224,
        ]);
        GameScreenshot::factory()->for($game)->ingame()->primary()->create([
            'width' => 320,
            'height' => 240,
        ]);

        return GameScreenshot::factory()->for($game)->title()->pending()->create([
            'width' => 320,
            'height' => 240,
        ]);
    });

    // ACT
    $warning = GameScreenshotModerationResource::getMixedPrimaryResolutionWarningData($pending);

    // ASSERT
    expect($warning)->toBeNull();
});

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
