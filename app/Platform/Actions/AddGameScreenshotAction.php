<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\GameScreenshotValidationService;
use App\Support\Media\CreateDoubledScreenshotAction;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AddGameScreenshotAction
{
    public function __construct(
        private readonly GameScreenshotValidationService $validationService = new GameScreenshotValidationService(),
    ) {
    }

    /**
     * The Atari 2600's TIA outputs frames with non-square pixels.
     * Emulators capture at native resolution, so we'll double the
     * width server-side to roughly match the CRT display.
     */
    private const ATARI_2600_BASE_WIDTH = 160;

    private const DIMENSION_TOLERANCE = 1;

    /**
     * @throws ValidationException
     */
    public function execute(
        Game $game,
        UploadedFile $file,
        ScreenshotType $type,
        ?string $description = null,
        bool $isPrimary = false,
    ): GameScreenshot {
        $this->validationService->validateFile($file, $game);

        [$width, $height] = getimagesize($file->getRealPath());
        $this->validationService->validateResolution($width, $height, $game);
        $hash = $this->validationService->validateHash($file, $game);
        $this->validateCap($game, $type, $isPrimary);

        $originalContents = file_get_contents($file->getRealPath());
        $mediaFilePath = $file->getRealPath();
        $doubledTempPath = null;
        $shouldDoubleWidth = $this->getShouldDoubleWidth($width, $game);

        // For Atari 2600 screenshots at native capture width,
        // double the width before giving the file to Spatie.
        if ($shouldDoubleWidth) {
            $doubledTempPath = (new CreateDoubledScreenshotAction())->execute($originalContents);
            $mediaFilePath = $doubledTempPath;
            $imageContents = file_get_contents($doubledTempPath);
            $width *= 2;
        } else {
            $imageContents = $originalContents;
        }

        // Auto-promote to primary if explicitly requested or if no approved screenshots of this type exist yet.
        $shouldBePrimary = $isPrimary || !$game->gameScreenshots()
            ->ofType($type)
            ->approved()
            ->exists();

        $legacyPath = null;
        if ($shouldBePrimary) {
            $legacyPath = (new CreateLegacyScreenshotPngAction())->execute($imageContents);

            $demotedStatus = match ($type) {
                ScreenshotType::Title, ScreenshotType::Completion => GameScreenshotStatus::Replaced,
                ScreenshotType::Ingame => GameScreenshotStatus::Pending,
            };

            $game->gameScreenshots()
                ->ofType($type)
                ->approved()
                ->update(['is_primary' => false, 'status' => $demotedStatus]);
        }

        $customProperties = ['sha1' => $hash];
        if ($legacyPath !== null) {
            $customProperties['legacy_path'] = $legacyPath;
        }

        try {
            $media = $game
                ->addMedia($mediaFilePath)
                ->preservingOriginal()
                ->withCustomProperties($customProperties)
                ->toMediaCollection('screenshots');
        } finally {
            if ($doubledTempPath !== null) {
                @unlink($doubledTempPath);
            }
        }

        // If we doubled the width, once the media exists in S3, colocate
        // the original capture alongside it for future recovery if needed.
        if ($shouldDoubleWidth) {
            $mediaDirectory = pathinfo($media->getPath(), PATHINFO_DIRNAME) . '/';
            $preservationPath = $mediaDirectory . 'original-capture.png';
            Storage::disk('s3')->put($preservationPath, $originalContents);

            $media->setCustomProperty('original_capture_path', $preservationPath);
            $media->save();
        }

        return GameScreenshot::create([
            'game_id' => $game->id,
            'media_id' => $media->id,
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'is_primary' => $shouldBePrimary,
            'status' => GameScreenshotStatus::Approved,
            'description' => $description,
        ]);
    }

    private function getShouldDoubleWidth(int $width, Game $game): bool
    {
        if ($game->system_id !== System::Atari2600) {
            return false;
        }

        return abs($width - self::ATARI_2600_BASE_WIDTH) <= self::DIMENSION_TOLERANCE;
    }

    /**
     * @throws ValidationException
     */
    private function validateCap(Game $game, ScreenshotType $type, bool $isPrimary = false): void
    {
        // When isPrimary is true, the caller intends to replace the existing
        // primary. The demotion logic runs after validation, so we skip the
        // cap check to allow the replacement flow to proceed.
        if ($isPrimary) {
            return;
        }

        $cap = $type->approvedCap();

        $approvedCount = $game->gameScreenshots()
            ->ofType($type)
            ->approved()
            ->count();

        if ($approvedCount >= $cap) {
            throw ValidationException::withMessages([
                'screenshot' => "This game has reached the maximum of {$cap} approved {$type->value} screenshot(s).",
            ]);
        }
    }
}
