<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\RejectGameScreenshotAction;
use App\Platform\Actions\SubmitPendingGameScreenshotAction;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

it('rejects a pending screenshot and records the rejection details', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $pending = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        UploadedFile::fake()->image('pending.png', 256, 224),
        ScreenshotType::Ingame,
        $submitter,
    );

    // ACT
    (new RejectGameScreenshotAction())->execute(
        $pending,
        $reviewer,
        GameScreenshotRejectionReason::PoorQuality,
        'Image is too blurry.',
    );

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($fresh->reviewed_by_user_id)->toEqual($reviewer->id);
    expect($fresh->reviewed_at)->not->toBeNull();
    expect($fresh->rejection_reason)->toEqual(GameScreenshotRejectionReason::PoorQuality);
    expect($fresh->rejection_notes)->toEqual('Image is too blurry.');
    expect(App\Models\UserDelayedSubscription::count())->toEqual(0);
});
