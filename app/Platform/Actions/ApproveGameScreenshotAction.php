<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotReviewDecision;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotResolutionService;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApproveGameScreenshotAction
{
    /**
     * @throws ValidationException
     */
    public function execute(GameScreenshot $screenshot, User $reviewer, ScreenshotReviewDecision $decision): void
    {
        DB::transaction(function () use ($screenshot, $reviewer, $decision): void {
            $screenshotKey = $screenshot->getKey();

            $lockedScreenshots = GameScreenshot::query()
                ->where('game_id', $screenshot->game_id)
                ->where('type', $screenshot->type)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /** @var GameScreenshot|null $screenshot */
            $screenshot = $lockedScreenshots->firstWhere('id', $screenshotKey);
            if (!$screenshot) {
                /** @var GameScreenshot $screenshot */
                $screenshot = GameScreenshot::query()
                    ->whereKey($screenshotKey)
                    ->firstOrFail();
            }

            if ($screenshot->status !== GameScreenshotStatus::Pending) {
                throw ValidationException::withMessages([
                    'screenshot' => 'This screenshot has already been reviewed.',
                ]);
            }

            $screenshot->loadMissing(['game.system', 'media']);

            $game = $screenshot->game;
            $type = $screenshot->type;

            if ($decision === ScreenshotReviewDecision::Reject) {
                throw ValidationException::withMessages([
                    'screenshot' => 'Use the reject action to reject screenshots.',
                ]);
            }

            if ($type === ScreenshotType::Title || $type === ScreenshotType::Completion) {
                if ($decision !== ScreenshotReviewDecision::Primary) {
                    throw ValidationException::withMessages([
                        'screenshot' => 'Title and completion screenshots must be approved as primary.',
                    ]);
                }

                $existingApprovedImage = $game->gameScreenshots()
                    ->ofType($type)
                    ->approved()
                    ->first();

                $this->promotePrimary($screenshot, $existingApprovedImage, $reviewer);
            }

            if ($type === ScreenshotType::Ingame) {
                $approvedCount = $game->gameScreenshots()
                    ->ofType(ScreenshotType::Ingame)
                    ->approved()
                    ->count();

                $existingPrimary = $game->gameScreenshots()
                    ->ofType(ScreenshotType::Ingame)
                    ->approved()
                    ->primary()
                    ->first();

                $isPromotion =
                    $decision === ScreenshotReviewDecision::Primary
                    || $decision === ScreenshotReviewDecision::PrimaryKeepGallery;

                $cap = ScreenshotType::Ingame->approvedCap();
                if (
                    $approvedCount >= $cap
                    && (
                        $decision === ScreenshotReviewDecision::Gallery
                        || $decision === ScreenshotReviewDecision::PrimaryKeepGallery
                        || !$existingPrimary
                    )
                ) {
                    throw ValidationException::withMessages([
                        'screenshot' => "This game has reached the maximum of {$cap} approved in-game screenshots.",
                    ]);
                }

                $system = $game->system;
                $resolutionService = new ScreenshotResolutionService();
                $systemValidatesResolutions = $system && !empty($system->screenshot_resolutions);

                $doesNewHaveValidResolution =
                    $systemValidatesResolutions
                    && $screenshot->width
                    && $screenshot->height
                    && $resolutionService->isValidResolution($screenshot->width, $screenshot->height, $system);

                if (
                    $isPromotion
                    && $existingPrimary
                    && $systemValidatesResolutions
                    && !$doesNewHaveValidResolution
                ) {
                    throw ValidationException::withMessages([
                        'screenshot' => 'This screenshot has an unsupported resolution and cannot replace the primary.',
                    ]);
                }

                if ($isPromotion) {
                    $this->promotePrimary(
                        $screenshot,
                        $existingPrimary,
                        $reviewer,
                        shouldRetireExisting: $decision === ScreenshotReviewDecision::Primary,
                    );
                }
            }

            (new PromoteGameScreenshotMediaToApprovedAction())->execute($screenshot);

            // Only assign a tail order for gallery additions. Primary screenshots are anchored
            // to the top of their type group by GameScreenshotObserver::moveToTopOfTypeGroup,
            // so overwriting their order_column here would push the primary behind its siblings.
            if (!$screenshot->is_primary) {
                $maxOrder = $game->gameScreenshots()
                    ->ofType($type)
                    ->approved()
                    ->max('order_column') ?? 0;

                $screenshot->order_column = $maxOrder + 1;
            }

            $screenshot->status = GameScreenshotStatus::Approved;
            $screenshot->reviewed_by_user_id = $reviewer->id;
            $screenshot->reviewed_at = now();
            $screenshot->save();

            if (
                $screenshot->captured_by_user_id
                && $screenshot->captured_by_user_id !== $reviewer->id
            ) {
                UserDelayedSubscription::updateOrCreate(
                    [
                        'user_id' => $screenshot->captured_by_user_id,
                        'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
                        'subject_id' => $screenshot->id,
                    ],
                    [
                        'first_update_id' => $screenshot->id,
                    ],
                );
            }
        }, attempts: 3);
    }

    private function promotePrimary(
        GameScreenshot $screenshot,
        ?GameScreenshot $existingPrimary,
        User $reviewer,
        bool $shouldRetireExisting = true,
    ): void {
        if ($existingPrimary) {
            $existingPrimary->update([
                'is_primary' => false,
                'status' => $shouldRetireExisting ? GameScreenshotStatus::Replaced : GameScreenshotStatus::Approved,
                'replaced_by_user_id' => $shouldRetireExisting ? $screenshot->captured_by_user_id : null,
            ]);
        }

        $this->ensureLegacyPng($screenshot);
        $screenshot->update(['is_primary' => true]);

        (new LogPrimaryScreenshotChangeAction())->execute(
            $screenshot->game,
            $screenshot->type,
            $existingPrimary,
            $screenshot,
            $reviewer,
        );
    }

    private function ensureLegacyPng(GameScreenshot $screenshot): void
    {
        $media = $screenshot->media;
        if (!$media || $media->getCustomProperty('legacy_path')) {
            return;
        }

        $fileContents = Storage::disk($media->disk)->get($media->getPath());
        if (!$fileContents) {
            return;
        }

        $legacyPath = (new CreateLegacyScreenshotPngAction())->execute($fileContents);
        if ($legacyPath) {
            $media->setCustomProperty('legacy_path', $legacyPath);
            $media->save();
        }
    }
}
