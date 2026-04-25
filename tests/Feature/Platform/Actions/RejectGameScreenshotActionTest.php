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
use App\Support\Alerts\InappropriateGameScreenshotAlert;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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

it('queues an alert when a screenshot is rejected for inappropriate content', function () {
    // ARRANGE
    Queue::fake();
    config(['services.discord.alerts_webhook.inappropriate_game_screenshot' => 'https://discord.com/api/webhooks/test']);

    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $pending = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        UploadedFile::fake()->image('pending.png', 256, 224),
        ScreenshotType::Completion,
        $submitter,
    );

    // ACT
    (new RejectGameScreenshotAction())->execute(
        $pending,
        $reviewer,
        GameScreenshotRejectionReason::InappropriateContent,
    );

    // ASSERT
    Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) use ($game, $submitter, $reviewer) {
        return
            $job->alert instanceof InappropriateGameScreenshotAlert
            && $job->alert->reviewer->is($reviewer)
            && $job->alert->screenshot->game->is($game)
            && $job->alert->screenshot->capturedBy->is($submitter);
    });
});

it('does not queue an alert for ordinary rejection reasons', function () {
    // ARRANGE
    Queue::fake();
    config(['services.discord.alerts_webhook.inappropriate_game_screenshot' => 'https://discord.com/api/webhooks/test']);

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
    );

    // ASSERT
    Queue::assertNotPushed(SendAlertWebhookJob::class);
});

it('allows system-driven rejections without a reviewer', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();

    $pending = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        UploadedFile::fake()->image('pending.png', 256, 224),
        ScreenshotType::Ingame,
        $submitter,
    );

    // ACT
    (new RejectGameScreenshotAction())->execute(
        $pending,
        null,
        GameScreenshotRejectionReason::Other,
        'User was muted',
    );

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($fresh->reviewed_by_user_id)->toBeNull();
    expect($fresh->rejection_reason)->toEqual(GameScreenshotRejectionReason::Other);
    expect($fresh->rejection_notes)->toEqual('User was muted');
});
