<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotResolutionService;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

class ApproveGameScreenshotAction
{
    /**
     * @throws ValidationException
     */
    public function execute(GameScreenshot $screenshot, User $reviewer): void
    {
        $game = $screenshot->game;
        $type = $screenshot->type;

        if ($type === ScreenshotType::Title || $type === ScreenshotType::Completion) {
            $existingApprovedImage = $game->gameScreenshots()
                ->ofType($type)
                ->approved()
                ->first();

            if ($existingApprovedImage) {
                // Replaced images won't get sent to the front-end.
                $existingApprovedImage->update([
                    'is_primary' => false,
                    'status' => GameScreenshotStatus::Replaced,
                ]);
            }

            $this->ensureLegacyPng($screenshot);
            $screenshot->update(['is_primary' => true]);
        }

        if ($type === ScreenshotType::Ingame) {
            $approvedCount = $game->gameScreenshots()
                ->ofType(ScreenshotType::Ingame)
                ->approved()
                ->count();

            if ($approvedCount >= 20) {
                throw ValidationException::withMessages([
                    'screenshot' => 'This game has reached the maximum of 20 approved in-game screenshots.',
                ]);
            }

            $existingPrimary = $game->gameScreenshots()
                ->ofType(ScreenshotType::Ingame)
                ->approved()
                ->primary()
                ->first();

            if ($existingPrimary) {
                $system = $game->system;
                $resolutionService = new ScreenshotResolutionService();

                $primaryHasInvalidResolution =
                    $system
                    && !empty($system->screenshot_resolutions)
                    && !$resolutionService->isValidResolution($existingPrimary->width, $existingPrimary->height, $system);

                $newHasValidResolution =
                    $system
                    && !empty($system->screenshot_resolutions)
                    && $resolutionService->isValidResolution($screenshot->width, $screenshot->height, $system);

                if ($primaryHasInvalidResolution && $newHasValidResolution) {
                    // Replaced images won't get sent to the front-end.
                    $existingPrimary->update([
                        'is_primary' => false,
                        'status' => GameScreenshotStatus::Replaced,
                    ]);

                    $this->ensureLegacyPng($screenshot);
                    $screenshot->update(['is_primary' => true]);
                }
            }
        }

        $media = $screenshot->media;
        if ($media && $media->collection_name === 'screenshots-pending') {
            $pathGenerator = PathGeneratorFactory::create($media);
            $oldPath = $pathGenerator->getPath($media);

            $media->collection_name = 'screenshots';
            $media->save();

            $newPath = $pathGenerator->getPath($media);
            $disk = Storage::disk($media->disk);

            foreach ($disk->allFiles($oldPath) as $file) {
                $disk->move($file, $newPath . Str::after($file, $oldPath));
            }

            app(FileManipulator::class)->createDerivedFiles($media);
        }

        $maxOrder = $game->gameScreenshots()
            ->ofType($type)
            ->approved()
            ->max('order_column') ?? 0;

        $screenshot->order_column = $maxOrder + 1;
        $screenshot->status = GameScreenshotStatus::Approved;
        $screenshot->reviewed_by_user_id = $reviewer->id;
        $screenshot->reviewed_at = now();
        $screenshot->save();
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
