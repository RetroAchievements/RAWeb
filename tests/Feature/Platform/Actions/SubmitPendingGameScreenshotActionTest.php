<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\SubmitPendingGameScreenshotAction;
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
