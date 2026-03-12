<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Platform\Actions\AddGameScreenshotAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

it('creates a primary game screenshot with media on first upload', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $file = UploadedFile::fake()->image('screenshot.png', 256, 224);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
    expect($screenshot->game_id)->toEqual($game->id);
    expect($screenshot->type)->toEqual(ScreenshotType::Ingame);
    expect($screenshot->status)->toEqual(GameScreenshotStatus::Approved);
    expect($screenshot->is_primary)->toBeTrue();
    expect($screenshot->media_id)->not->toBeNull();

    $media = $game->fresh()->getMedia('screenshots')->first();
    expect($media->getCustomProperty('sha1'))->not->toBeNull();
    expect($media->getCustomProperty('width'))->toEqual(256);
    expect($media->getCustomProperty('height'))->toEqual(224);
});

it('does not set subsequent screenshots as primary', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $action = new AddGameScreenshotAction();
    $action->execute($game, UploadedFile::fake()->image('first.png', 256, 224), ScreenshotType::Ingame);

    // ACT
    $second = $action->execute($game, UploadedFile::fake()->image('second.png', 320, 240), ScreenshotType::Ingame);

    // ASSERT
    expect($second->is_primary)->toBeFalse();
});

it('demotes existing primary image when a new screenshot is forced as primary', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $action = new AddGameScreenshotAction();
    $first = $action->execute($game, UploadedFile::fake()->image('first.png', 256, 224), ScreenshotType::Ingame);

    // ACT
    $second = $action->execute($game, UploadedFile::fake()->image('second.png', 320, 240), ScreenshotType::Ingame, isPrimary: true);

    // ASSERT
    expect($second->is_primary)->toBeTrue();
    expect($second->status)->toEqual(GameScreenshotStatus::Approved);
    expect($first->fresh()->is_primary)->toBeFalse();
    expect($first->fresh()->status)->toEqual(GameScreenshotStatus::Pending);
});

it('rejects duplicate images for the same game', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $action = new AddGameScreenshotAction();

    // ... both files need identical bytes so the SHA1 hash collides ...
    $source = UploadedFile::fake()->image('screenshot.png', 256, 224);
    $sourceContent = file_get_contents($source->getRealPath());

    $action->execute($game, $source, ScreenshotType::Ingame);

    $duplicate = UploadedFile::fake()->image('duplicate.png', 256, 224);
    file_put_contents($duplicate->getRealPath(), $sourceContent);

    // ASSERT
    $action->execute($game->fresh(), $duplicate, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('enforces a cap of 20 approved ingame screenshots', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    GameScreenshot::factory()->count(20)->for($game)->ingame()->create();
    $file = UploadedFile::fake()->image('screenshot.png', 256, 224);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('does not enforce the ingame cap for title screenshots', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    GameScreenshot::factory()->count(20)->for($game)->ingame()->create();
    $file = UploadedFile::fake()->image('title.png', 256, 224);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Title);

    // ASSERT
    expect($screenshot->type)->toEqual(ScreenshotType::Title);
});

it('enforces a cap of 1 approved screenshot for title and completion types', function (ScreenshotType $type) {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    GameScreenshot::factory()->for($game)->state(['type' => $type])->create();
    $file = UploadedFile::fake()->image('screenshot.png', 256, 224);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, $type);
})->throws(ValidationException::class)->with([
    'title' => [ScreenshotType::Title],
    'completion' => [ScreenshotType::Completion],
]);

it('rejects a file smaller than 64x64', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $file = UploadedFile::fake()->image('tiny.png', 32, 32);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('rejects a file larger than 1920x1080', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $file = UploadedFile::fake()->image('huge.png', 2560, 1440);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('stores the description', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $file = UploadedFile::fake()->image('screenshot.png', 256, 224);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        description: 'Boss fight in stage 3',
    );

    // ASSERT
    expect($screenshot->description)->toEqual('Boss fight in stage 3');
});

it('accepts a screenshot matching an exact base resolution', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 256, 224);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
});

it('rejects a screenshot with wrong dimensions for the system', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 320, 240);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('accepts a 2x scaled screenshot', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 320, 'height' => 240]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 640, 480);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
});

it('accepts a 3x scaled screenshot', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 320, 'height' => 240]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 960, 720);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
});

it('rejects a 4x scaled screenshot', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 640, 576);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('allows any dimensions when the system has null resolutions', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => null,
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 800, 600);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
});

it('treats different screenshot types independently for primary', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $action = new AddGameScreenshotAction();

    // ACT
    $ingame = $action->execute($game, UploadedFile::fake()->image('ingame.png', 256, 224), ScreenshotType::Ingame);
    $title = $action->execute($game, UploadedFile::fake()->image('title.png', 320, 240), ScreenshotType::Title);

    // ASSERT
    expect($ingame->is_primary)->toBeTrue();
    expect($title->is_primary)->toBeTrue();
});

it('accepts an SMPTE 601 NTSC resolution for a system with analog TV output', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 320, 'height' => 224]],
        'has_analog_tv_output' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 720, 480);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
});

it('accepts an SMPTE 601 PAL resolution for a system with analog TV output', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 320, 'height' => 224]],
        'has_analog_tv_output' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 720, 576);

    // ACT
    $screenshot = (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);

    // ASSERT
    expect($screenshot)->toBeInstanceOf(GameScreenshot::class);
});

it('rejects a 3x multiple that exceeds the 1920x1080 hard cap', function () {
    // ARRANGE
    // ... 640x400 * 3 = 1920x1200 - this passes resolution validation but exceeds the dimension cap ...
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 640, 'height' => 400]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 1920, 1200);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);

it('rejects an SMPTE 601 resolution for a handheld system', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $file = UploadedFile::fake()->image('screenshot.png', 720, 480);

    // ASSERT
    (new AddGameScreenshotAction())->execute($game, $file, ScreenshotType::Ingame);
})->throws(ValidationException::class);
