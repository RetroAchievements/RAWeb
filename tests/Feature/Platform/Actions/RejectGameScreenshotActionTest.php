<?php

declare(strict_types=1);

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
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
use Illuminate\Validation\ValidationException;

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

    $delayedSubscription = UserDelayedSubscription::sole(); // only one
    expect($delayedSubscription->user_id)->toEqual($submitter->id);
    expect($delayedSubscription->subject_type)->toEqual(SubscriptionSubjectType::GameScreenshotDecision);
    expect($delayedSubscription->subject_id)->toEqual($fresh->id);
    expect($delayedSubscription->first_update_id)->toEqual($fresh->id);
});

it('rejects screenshots that have already been reviewed', function () {
    // ARRANGE
    $reviewer = User::factory()->create();
    $approved = GameScreenshot::factory()
        ->for(Game::factory()->create(['system_id' => System::factory()]))
        ->ingame()
        ->create([
            'status' => GameScreenshotStatus::Approved,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

    // ACT
    $attempt = fn () => (new RejectGameScreenshotAction())->execute(
        $approved,
        $reviewer,
        GameScreenshotRejectionReason::PoorQuality,
    );

    // ASSERT
    expect($attempt)->toThrow(ValidationException::class, 'This screenshot has already been reviewed.');
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

    $delayedSubscription = UserDelayedSubscription::sole(); // only one
    expect($delayedSubscription->user_id)->toEqual($submitter->id);
    expect($delayedSubscription->subject_type)->toEqual(SubscriptionSubjectType::GameScreenshotDecision);
    expect($delayedSubscription->subject_id)->toEqual($pending->id);
    expect($delayedSubscription->first_update_id)->toEqual($pending->id);
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
