<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Rules\DisallowAnimatedImageRule;
use App\Rules\ValidScreenshotResolutionRule;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use App\Support\MediaLibrary\RejectedHashes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddGameScreenshotAction
{
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
        $this->validateFile($file);
        $this->validateResolution($file, $game);
        $hash = $this->validateHash($file, $game);
        $this->validateCap($game, $type, $isPrimary);

        // Auto-promote to primary if explicitly requested or if no approved screenshots of this type exist yet.
        $shouldBePrimary = $isPrimary || !$game->gameScreenshots()
            ->ofType($type)
            ->approved()
            ->exists();

        $legacyPath = null;
        if ($shouldBePrimary) {
            // Create the legacy /Images/NNNNNN.png file while we still have the local file on disk.
            $legacyPath = (new CreateLegacyScreenshotPngAction())->execute(
                file_get_contents($file->getRealPath())
            );

            // Demote existing approved screenshots of this type to pending. This
            // prevents the 20-screenshot cap from being hit by normal editor
            // uploads and keeps demoted screenshots available for future gallery
            // management (Set as Primary, Delete, etc).
            $game->gameScreenshots()
                ->ofType($type)
                ->approved()
                ->update(['is_primary' => false, 'status' => GameScreenshotStatus::Pending]);
        }

        // Add the file to the screenshots MediaLibrary collection.
        [$width, $height] = getimagesize($file->getRealPath());
        $customProperties = ['sha1' => $hash, 'width' => $width, 'height' => $height];
        if ($legacyPath !== null) {
            $customProperties['legacy_path'] = $legacyPath;
        }

        $media = $game
            ->addMedia($file->getRealPath())
            ->preservingOriginal()
            ->withCustomProperties($customProperties)
            ->toMediaCollection('screenshots');

        return GameScreenshot::create([
            'game_id' => $game->id,
            'media_id' => $media->id,
            'type' => $type,
            'is_primary' => $shouldBePrimary,
            'status' => GameScreenshotStatus::Approved,
            'description' => $description,
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function validateFile(UploadedFile $file): void
    {
        // A 1920x1080 hard cap bounds the maximum file size and
        // prevents unreasonably large uploads. Any system whose
        // 3x multiple exceeds this (eg: PC-98 at 1920x1200) will
        // be rejected here before the resolution rule runs.
        $validator = Validator::make(
            ['screenshot' => $file],
            ['screenshot' => [
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:4096',
                'dimensions:min_width=64,min_height=64,max_width=1920,max_height=1080',
                new DisallowAnimatedImageRule(),
            ]],
        );

        $validator->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validateHash(UploadedFile $file, Game $game): string
    {
        $hash = sha1_file($file->getRealPath());

        if (in_array($hash, RejectedHashes::IMAGE_HASHES_GAMES)) {
            throw ValidationException::withMessages([
                'screenshot' => 'This image is a known placeholder and cannot be uploaded.',
            ]);
        }

        // Reject duplicates based on SHA1 within this game's screenshots collection.
        $isDuplicate = $game->media()
            ->where('collection_name', 'screenshots')
            ->where('custom_properties->sha1', $hash)
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'screenshot' => 'This image has already been uploaded for this game.',
            ]);
        }

        return $hash;
    }

    /**
     * @throws ValidationException
     */
    private function validateResolution(UploadedFile $file, Game $game): void
    {
        $system = $game->system;
        if (!$system) {
            return;
        }

        $validator = Validator::make(
            ['screenshot' => $file],
            ['screenshot' => [new ValidScreenshotResolutionRule($system)]],
        );

        $validator->validate();
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


        $cap = match ($type) {
            ScreenshotType::Ingame => 20,
            ScreenshotType::Title, ScreenshotType::Completion => 1,
        };

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
