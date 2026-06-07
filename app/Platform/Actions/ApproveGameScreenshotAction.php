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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

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

                $this->promotePrimary($screenshot, $existingApprovedImage);
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

                $cap = ScreenshotType::Ingame->approvedCap();
                if (
                    $approvedCount >= $cap
                    && ($decision === ScreenshotReviewDecision::Gallery || !$existingPrimary)
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
                    $decision === ScreenshotReviewDecision::Primary
                    && $existingPrimary
                    && $systemValidatesResolutions
                    && !$doesNewHaveValidResolution
                ) {
                    throw ValidationException::withMessages([
                        'screenshot' => 'This screenshot has an unsupported resolution and cannot replace the primary.',
                    ]);
                }

                if ($decision === ScreenshotReviewDecision::Primary) {
                    $this->promotePrimary($screenshot, $existingPrimary);
                }
            }

            $media = $screenshot->media;

            if ($media && $media->collection_name === 'screenshots-pending') {
                $pathGenerator = PathGeneratorFactory::create($media);
                $oldPath = $pathGenerator->getPath($media);

                $media->collection_name = 'screenshots';
                $newPath = $pathGenerator->getPath($media);

                $this->moveApprovedScreenshotMedia(
                    media: $media,
                    oldPath: $oldPath,
                    newPath: $newPath,
                );

                $media->save();
                $this->queueApprovedScreenshotConversions($media);
            }

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

    private function promotePrimary(GameScreenshot $screenshot, ?GameScreenshot $existingPrimary): void
    {
        if ($existingPrimary) {
            // replaced images won't get sent to the front-end
            $existingPrimary->update([
                'is_primary' => false,
                'status' => GameScreenshotStatus::Replaced,
            ]);
        }

        $this->ensureLegacyPng($screenshot);
        $screenshot->update(['is_primary' => true]);
    }

    private function moveApprovedScreenshotMedia(Media $media, string $oldPath, string $newPath): void
    {
        $disk = Storage::disk($media->disk);

        foreach ($disk->allFiles($oldPath) as $file) {
            $newFile = $newPath . Str::after($file, $oldPath);

            $disk->move($file, $newFile);
        }
    }

    private function queueApprovedScreenshotConversions(Media $media): void
    {
        // defer medialibrary conversion generation until after the DB transaction commits
        // we don't want to generate thumbnails for a screenshot whose transaction failed
        DB::afterCommit(function () use ($media): void {
            app(FileManipulator::class)->createDerivedFiles($media);
        });
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
