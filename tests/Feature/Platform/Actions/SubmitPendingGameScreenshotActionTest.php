<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\SubmitPendingGameScreenshotAction;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

it('doubles the width for an Atari 2600 screenshot submitted at native capture resolution', function () {
    // ARRANGE
    $system = System::factory()->create([
        'id' => System::Atari2600,
        'screenshot_resolutions' => [['width' => 160, 'height' => 228]],
        'has_analog_tv_output' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('native.png', 160, 228);

    // ACT
    $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    expect($screenshot->width)->toEqual(320);
    expect($screenshot->height)->toEqual(228);
    expect($screenshot->media->getCustomProperty('original_capture_path'))->not->toBeNull();
});

it('preserves the original capture alongside the doubled pending media for an Atari 2600 submission', function () {
    // ARRANGE
    $system = System::factory()->create([
        'id' => System::Atari2600,
        'screenshot_resolutions' => [['width' => 160, 'height' => 228]],
        'has_analog_tv_output' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('native.png', 160, 228);

    // ACT
    $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    $media = $screenshot->media;
    $preservedFilename = $media->getCustomProperty('original_capture_path');
    expect($preservedFilename)->toEqual('original-capture.png');

    $directory = PathGeneratorFactory::create($media)->getPath($media);
    Storage::disk('s3')->assertExists($directory . $preservedFilename);
});

it('does not double the width for a non-Atari pending submission', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 160, 'height' => 144]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('native.png', 160, 144);

    // ACT
    $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    expect($screenshot->width)->toEqual(160);
    expect($screenshot->height)->toEqual(144);
    expect($screenshot->media->getCustomProperty('original_capture_path'))->toBeNull();
});

it('does not re-double the width for an Atari 2600 submission already uploaded at 320 wide', function () {
    // ARRANGE
    $system = System::factory()->create([
        'id' => System::Atari2600,
        'screenshot_resolutions' => [['width' => 160, 'height' => 228]],
        'has_analog_tv_output' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('already-doubled.png', 320, 228);

    // ACT
    $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    expect($screenshot->width)->toEqual(320);
    expect($screenshot->height)->toEqual(228);
    expect($screenshot->media->getCustomProperty('original_capture_path'))->toBeNull();
});

it('enforces the configurable pending submission cap', function () {
    // ARRANGE
    config()->set('screenshots.max_pending_submissions_per_user', 3);

    $game = Game::factory()->create(['system_id' => System::factory()]);
    $user = User::factory()->create();

    GameScreenshot::factory()->count(3)->for($game)->pending()->create([
        'captured_by_user_id' => $user->id,
    ]);

    // ACT
    $attempt = fn () => (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        UploadedFile::fake()->image('next.png', 256, 224),
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    expect($attempt)->toThrow(ValidationException::class);
});

it('blocks resubmission of the same file when a prior submission was rejected for a non-companion reason', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('shot.png', 256, 224);

    $submitted = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );
    $submitted->update([
        'status' => GameScreenshotStatus::Rejected,
        'rejection_reason' => GameScreenshotRejectionReason::PoorQuality,
    ]);

    // ACT
    $attempt = fn () => (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    expect($attempt)->toThrow(ValidationException::class);
});

it('allows resubmission of the same file when the only prior match was rejected as missing a matching companion', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('shot.png', 256, 224);

    $submitted = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );
    $submitted->update([
        'status' => GameScreenshotStatus::Rejected,
        'rejection_reason' => GameScreenshotRejectionReason::MissingMatchingCompanion,
    ]);

    // ACT
    $resubmitted = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        $file,
        ScreenshotType::Ingame,
        $user,
    );

    // ASSERT
    expect($resubmitted->id)->not->toEqual($submitted->id);
    expect($resubmitted->status)->toEqual(GameScreenshotStatus::Pending);
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(2);
});
