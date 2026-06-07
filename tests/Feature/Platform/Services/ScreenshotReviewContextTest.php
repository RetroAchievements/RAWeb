<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ScreenshotReviewDecision;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotReviewContext;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @param array<int, GameScreenshot> $screenshots
 */
function setReviewGameScreenshots(GameScreenshot $subject, Game $game, array $screenshots): void
{
    $game->load('system');
    $subject->setRelation('game', $game->setRelation('gameScreenshots', collect($screenshots)));
}

describe('Approved Resolution Mismatches', function () {
    it('returns the mismatched resolutions grouped by WxH with counts and type breakdown', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [
                ['width' => 1280, 'height' => 720],
                ['width' => 1920, 'height' => 1400],
            ],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$approvedTitle, $approvedIngame, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => 1280,
                    'height' => 720,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 1920,
                    'height' => 1400,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'width' => 1280,
                    'height' => 720,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$approvedTitle, $approvedIngame, $pending]);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->approvedResolutionMismatches())->toEqual([
            '1920x1400' => ['count' => 1, 'types' => ['In-game' => 1]],
        ]);
    });

    it('ignores approved screenshots whose own resolution is invalid for the system', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [['width' => 1280, 'height' => 720]],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$approvedLegacy, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 999,
                    'height' => 444,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'width' => 1280,
                    'height' => 720,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$approvedLegacy, $pending]);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->approvedResolutionMismatches())->toEqual([]);
    });

    it('excludes pending, rejected, and replaced screenshots from the comparison set', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$rejected, $otherPending, $subject] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->rejected()->create([
                    'width' => 1920,
                    'height' => 1080,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'width' => 1920,
                    'height' => 1080,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'width' => 256,
                    'height' => 224,
                ]),
            ];
        });

        setReviewGameScreenshots($subject, $game, [$rejected, $otherPending, $subject]);

        // ASSERT
        expect(ScreenshotReviewContext::make($subject)->approvedResolutionMismatches())->toEqual([]);
    });

    it('loads game screenshots before checking mismatches', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [
                ['width' => 256, 'height' => 224],
                ['width' => 1280, 'height' => 720],
            ],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameScreenshot::withoutEvents(function () use ($game): void {
            GameScreenshot::factory()->for($game)->title()->primary()->create([
                'width' => 1280,
                'height' => 720,
            ]);
        });

        $pending = GameScreenshot::factory()->for($game)->ingame()->pending()->create([
            'width' => 256,
            'height' => 224,
        ])->fresh(['game.system']);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->approvedResolutionMismatches())->toEqual([
            '1280x720' => ['count' => 1, 'types' => ['Title' => 1]],
        ]);
    });

});

describe('Review Plan', function () {
    it('recommends primary approval when the submission fixes an invalid primary', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [['width' => 1280, 'height' => 720]],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$titlePrimary, $invalidIngamePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => 1280,
                    'height' => 720,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 999,
                    'height' => 444,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                    'width' => 1280,
                    'height' => 720,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $invalidIngamePrimary, $pending]);

        // ACT
        $context = ScreenshotReviewContext::make($pending);

        // ASSERT
        expect($context->canFixCurrentPrimary())->toBeTrue();
        expect($context->recommendedAction())->toEqual(ScreenshotReviewDecision::Primary);
        expect($context->candidateImageCues())->toContain([
            'modalLabel' => 'Can replace invalid primary',
            'badgeLabel' => 'Fixes invalid primary',
            'tone' => 'success',
        ]);
    });

    it('recommends rejection when no pending companion can resolve a primary size mismatch', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [
                ['width' => 256, 'height' => 224],
                ['width' => 256, 'height' => 240],
            ],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$titlePrimary, $completionPrimary, $ingamePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => 256,
                    'height' => 224,
                ]),
                GameScreenshot::factory()->for($game)->completion()->primary()->create([
                    'width' => 256,
                    'height' => 240,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 256,
                    'height' => 224,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                    'width' => 256,
                    'height' => 240,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $completionPrimary, $ingamePrimary, $pending]);

        // ACT
        $context = ScreenshotReviewContext::make($pending);

        // ASSERT
        expect($context->hasUnresolvedPrimaryPreviewMismatch())->toBeTrue();
        expect($context->mismatchedPrimaryTypes())->toContain(ScreenshotType::Title);
        expect($context->recommendedAction())->toEqual(ScreenshotReviewDecision::Reject);
        expect($context->candidateImageCues())->toContain([
            'modalLabel' => 'No pending Title matches this size',
            'badgeLabel' => 'No matching Title',
            'tone' => 'danger',
        ]);
    });

    it('uses matching pending companion types to avoid an unresolved primary mismatch', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [
                ['width' => 256, 'height' => 224],
                ['width' => 256, 'height' => 240],
            ],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);
        $submitter = User::factory()->create();

        [$titlePrimary, $completionPrimary, $ingamePrimary, $pending, $matchingTitle] = GameScreenshot::withoutEvents(function () use ($game, $submitter) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => 256,
                    'height' => 224,
                ]),
                GameScreenshot::factory()->for($game)->completion()->primary()->create([
                    'width' => 256,
                    'height' => 240,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 256,
                    'height' => 224,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => $submitter->id,
                    'width' => 256,
                    'height' => 240,
                ]),
                GameScreenshot::factory()->for($game)->title()->pending()->create([
                    'captured_by_user_id' => $submitter->id,
                    'width' => 256,
                    'height' => 240,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $completionPrimary, $ingamePrimary, $pending, $matchingTitle]);

        // ACT
        $context = ScreenshotReviewContext::make($pending);

        // ASSERT
        expect($context->hasUnresolvedPrimaryPreviewMismatch())->toBeFalse();
        expect($context->matchingPendingCompanionTypes())->toContain(ScreenshotType::Title);
        expect($context->recommendedAction())->toEqual(ScreenshotReviewDecision::Primary);
    });

    it('recommends galery approval when the candidate matches the current ingame primary and the gallery has room', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [['width' => 1280, 'height' => 720]],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$ingamePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 1280,
                    'height' => 720,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                    'width' => 1280,
                    'height' => 720,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$ingamePrimary, $pending]);

        // ACT
        $context = ScreenshotReviewContext::make($pending);

        // ASSERT
        expect($context->canApproveToGallery())->toBeTrue();
        expect($context->recommendedAction())->toEqual(ScreenshotReviewDecision::Gallery);
    });

    it('does not allow gallery approval when the candidate would auto-promote over an invalid ingame primary', function () {
        // ARRANGE
        $system = System::factory()->create([
            'screenshot_resolutions' => [['width' => 1280, 'height' => 720]],
        ]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$ingamePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'width' => 999,
                    'height' => 444,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                    'width' => 1280,
                    'height' => 720,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$ingamePrimary, $pending]);

        // ACT
        $context = ScreenshotReviewContext::make($pending);

        // ASSERT
        expect($context->willPromoteIngameToPrimary())->toBeTrue();
        expect($context->canApproveToGallery())->toBeFalse();
        expect($context->recommendedAction())->toEqual(ScreenshotReviewDecision::Primary);
    });

    it('returns the current primary for the submission type', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$existingPrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => 1280,
                    'height' => 720,
                ]),
                GameScreenshot::factory()->for($game)->title()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                    'width' => 1280,
                    'height' => 720,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$existingPrimary, $pending]);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->currentPrimaryForType(ScreenshotType::Title)?->id)->toEqual($existingPrimary->id);
    });
});
