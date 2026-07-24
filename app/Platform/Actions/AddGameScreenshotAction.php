<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\Atari2600WidthDoubler;
use App\Platform\Services\GameScreenshotValidationService;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddGameScreenshotAction
{
    public function __construct(
        private readonly GameScreenshotValidationService $validationService = new GameScreenshotValidationService(),
        private readonly Atari2600WidthDoubler $widthDoubler = new Atari2600WidthDoubler(),
    ) {
    }

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

        $prepared = $this->widthDoubler->prepare($file->getRealPath(), $width, $game);

        // Auto-promote to primary if explicitly requested or if no approved screenshots of this type exist yet.
        $shouldBePrimary = $isPrimary || !$game->gameScreenshots()
            ->ofType($type)
            ->approved()
            ->exists();

        $legacyPath = $shouldBePrimary
            ? (new CreateLegacyScreenshotPngAction())->execute(file_get_contents($prepared->filePath))
            : null;

        $customProperties = ['sha1' => $hash];
        if ($legacyPath !== null) {
            $customProperties['legacy_path'] = $legacyPath;
        }

        try {
            $media = $game
                ->addMedia($prepared->filePath)
                ->preservingOriginal()
                ->withCustomProperties($customProperties)
                ->toMediaCollection('screenshots');
        } finally {
            $prepared->cleanup();
        }

        $prepared->finalize($media);

        /** @var User|null $causer */
        $causer = Auth::user();

        return DB::transaction(function () use (
            $game,
            $type,
            $description,
            $shouldBePrimary,
            $media,
            $prepared,
            $height,
            $causer,
        ): GameScreenshot {
            $previousPrimary = null;

            if ($shouldBePrimary) {
                $previousPrimary = $game->gameScreenshots()
                    ->ofType($type)
                    ->approved()
                    ->primary()
                    ->lockForUpdate()
                    ->first();

                $demotedStatus = match ($type) {
                    ScreenshotType::Title, ScreenshotType::Completion => GameScreenshotStatus::Replaced,
                    ScreenshotType::Ingame => GameScreenshotStatus::Pending,
                };

                $demotedAttributes = ['is_primary' => false, 'status' => $demotedStatus];
                if ($demotedStatus === GameScreenshotStatus::Replaced) {
                    $demotedAttributes['replaced_by_user_id'] = $causer?->id;
                }

                $game->gameScreenshots()
                    ->ofType($type)
                    ->approved()
                    ->update($demotedAttributes);
            }

            $created = GameScreenshot::create([
                'game_id' => $game->id,
                'media_id' => $media->id,
                'width' => $prepared->width,
                'height' => $height,
                'type' => $type,
                'is_primary' => $shouldBePrimary,
                'status' => GameScreenshotStatus::Approved,
                'description' => $description,
            ]);

            if ($shouldBePrimary) {
                (new LogPrimaryScreenshotChangeAction())->execute(
                    $game,
                    $type,
                    $previousPrimary,
                    $created,
                    $causer,
                );
            }

            return $created;
        });
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
