<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotReviewDecision;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotReviewContext;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

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

    it('allows gallery approval without recommending it when the candidate matches the current ingame primary and the gallery has room', function () {
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
        expect($context->recommendedAction())->toBeNull();
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

    it('allows keeping the current ingame primary in the gallery when there is room under the cap', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$ingamePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->primary()->create(),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$ingamePrimary, $pending]);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->canKeepReplacedPrimaryInGallery())->toBeTrue();
    });

    it('does not allow keeping the current ingame primary in the gallery when the game is at the cap', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$approved, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            $primary = GameScreenshot::factory()->for($game)->ingame()->primary()->create();
            $rest = GameScreenshot::factory()->count(9)->for($game)->ingame()->create()->all();

            return [
                array_merge([$primary], $rest),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [...$approved, $pending]);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->canKeepReplacedPrimaryInGallery())->toBeFalse();
    });

    it('does not allow keeping the current primary in the gallery for non-ingame screenshots', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$titlePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create(),
                GameScreenshot::factory()->for($game)->title()->pending()->create([
                    'captured_by_user_id' => User::factory()->create()->id,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $pending]);

        // ASSERT
        expect(ScreenshotReviewContext::make($pending)->canKeepReplacedPrimaryInGallery())->toBeFalse();
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

describe('Current Primary Context Items', function () {
    beforeEach(function () {
        Storage::fake('s3');
    });

    it('returns the zoom label and image rendering for current primaries', function () {
        // ARRANGE
        $system = System::factory()->create(['supports_upscaled_screenshots' => true]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$titlePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => 320,
                    'height' => 238,
                ]),
                GameScreenshot::factory()->for($game)->title()->pending()->create(),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $pending]);

        // ACT
        $context = ScreenshotReviewContext::make($pending);
        $item = $context->currentPrimaryContextItems()[0];

        // ASSERT
        expect($item['label'])->toBe('Title primary (320x238)');
        expect($item['imageRendering'])->toBe('crisp-edges');
    });

    it('returns pixelated image rendering for primaries on systems without upscaled screenshot support', function () {
        // ARRANGE
        $system = System::factory()->create(['supports_upscaled_screenshots' => false]);
        $game = Game::factory()->create(['system_id' => $system->id]);

        [$titlePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create(),
                GameScreenshot::factory()->for($game)->title()->pending()->create(),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $pending]);

        // ACT
        $item = ScreenshotReviewContext::make($pending)->currentPrimaryContextItems()[0];

        // ASSERT
        expect($item['imageRendering'])->toBe('pixelated');
    });

    it('uses an unknown resolution placeholder when a primary has unknown dimensions', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$titlePrimary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->title()->primary()->create([
                    'width' => null,
                    'height' => null,
                ]),
                GameScreenshot::factory()->for($game)->title()->pending()->create(),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$titlePrimary, $pending]);

        // ACT
        $item = ScreenshotReviewContext::make($pending)->currentPrimaryContextItems()[0];

        // ASSERT
        expect($item['label'])->toBe('Title primary (?)');
    });
});

describe('Other Pending For Game', function () {
    it('returns pending shots of any type for the same game, excluding the candidate and regardless of submitter', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);
        $submitter = User::factory()->create();
        $otherSubmitter = User::factory()->create();

        [$ingameSibling, $titleSibling, $pending] = GameScreenshot::withoutEvents(function () use ($game, $submitter, $otherSubmitter) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => $submitter->id,
                ]),
                GameScreenshot::factory()->for($game)->title()->pending()->create([
                    'captured_by_user_id' => $otherSubmitter->id,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create([
                    'captured_by_user_id' => $submitter->id,
                ]),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$ingameSibling, $titleSibling, $pending]);

        // ASSERT
        $ids = ScreenshotReviewContext::make($pending)->otherPendingForGame()->pluck('id')->all();
        expect($ids)->toEqualCanonicalizing([$ingameSibling->id, $titleSibling->id]);
    });

    it('excludes non-pending statuses', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$approvedSameGame, $rejectedSameGame, $validSibling, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->primary()->create(),
                GameScreenshot::factory()->for($game)->ingame()->rejected()->create(),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create(),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create(),
            ];
        });

        setReviewGameScreenshots($pending, $game, [
            $approvedSameGame,
            $rejectedSameGame,
            $validSibling,
            $pending,
        ]);

        // ASSERT
        $ids = ScreenshotReviewContext::make($pending)->otherPendingForGame()->pluck('id')->all();
        expect($ids)->toEqual([$validSibling->id]);
    });
});

describe('Approved Gallery Screenshots', function () {
    it('returns approved in-game screenshots ordered by order_column, including the primary', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$gallerySecond, $galleryFirst, $primary, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->create([
                    'is_primary' => false,
                    'status' => GameScreenshotStatus::Approved,
                    'order_column' => 20,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->create([
                    'is_primary' => false,
                    'status' => GameScreenshotStatus::Approved,
                    'order_column' => 10,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->primary()->create([
                    'order_column' => 1,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create(),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$gallerySecond, $galleryFirst, $primary, $pending]);

        // ASSERT
        $ids = ScreenshotReviewContext::make($pending)->approvedGalleryScreenshots()->pluck('id')->all();
        expect($ids)->toEqual([$primary->id, $galleryFirst->id, $gallerySecond->id]);
    });

    it('excludes rejected, pending, and non-ingame screenshots', function () {
        // ARRANGE
        $game = Game::factory()->create(['system_id' => System::factory()]);

        [$includedGallery, $rejected, $pendingShot, $titleApproved, $pending] = GameScreenshot::withoutEvents(function () use ($game) {
            return [
                GameScreenshot::factory()->for($game)->ingame()->create([
                    'is_primary' => false,
                    'status' => GameScreenshotStatus::Approved,
                ]),
                GameScreenshot::factory()->for($game)->ingame()->rejected()->create(),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create(),
                GameScreenshot::factory()->for($game)->title()->primary()->create(),
                GameScreenshot::factory()->for($game)->ingame()->pending()->create(),
            ];
        });

        setReviewGameScreenshots($pending, $game, [$includedGallery, $rejected, $pendingShot, $titleApproved, $pending]);

        // ASSERT
        $ids = ScreenshotReviewContext::make($pending)->approvedGalleryScreenshots()->pluck('id')->all();
        expect($ids)->toEqual([$includedGallery->id]);
    });
});
