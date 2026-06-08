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

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
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
