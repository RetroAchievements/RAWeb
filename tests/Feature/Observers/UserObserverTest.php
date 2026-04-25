<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it("rejects a user's pending screenshots when they are muted", function () {
    // ARRANGE
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()]);

    $pendingScreenshotA = GameScreenshot::factory()->for($game)->pending()->create([
        'captured_by_user_id' => $user->id,
    ]);
    $pendingScreenshotB = GameScreenshot::factory()->for($game)->pending()->create([
        'captured_by_user_id' => $user->id,
    ]);
    $approvedScreenshot = GameScreenshot::factory()->for($game)->create([
        'captured_by_user_id' => $user->id,
    ]);
    $otherUsersPendingScreenshot = GameScreenshot::factory()->for($game)->pending()->create([
        'captured_by_user_id' => $otherUser->id,
    ]);

    // ACT
    $user->update([
        'muted_until' => now()->addWeek(),
    ]);

    $pendingScreenshotA->refresh();
    $pendingScreenshotB->refresh();
    $approvedScreenshot->refresh();
    $otherUsersPendingScreenshot->refresh();

    // ASSERT
    expect($pendingScreenshotA->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($pendingScreenshotA->rejection_reason)->toEqual(GameScreenshotRejectionReason::Other);
    expect($pendingScreenshotA->rejection_notes)->toEqual('User was muted');
    expect($pendingScreenshotA->reviewed_by_user_id)->toBeNull();

    expect($pendingScreenshotB->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($pendingScreenshotB->rejection_reason)->toEqual(GameScreenshotRejectionReason::Other);
    expect($pendingScreenshotB->rejection_notes)->toEqual('User was muted');

    expect($approvedScreenshot->status)->toEqual(GameScreenshotStatus::Approved);
    expect($otherUsersPendingScreenshot->status)->toEqual(GameScreenshotStatus::Pending);
});
