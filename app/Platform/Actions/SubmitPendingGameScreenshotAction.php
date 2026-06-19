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
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SubmitPendingGameScreenshotAction
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
        User $user,
    ): GameScreenshot {
        $pendingCount = GameScreenshot::where('captured_by_user_id', $user->id)
            ->where('status', GameScreenshotStatus::Pending)
            ->count();

        $maxPendingSubmissions = max(1, (int) config('screenshots.max_pending_submissions_per_user'));

        if ($pendingCount >= $maxPendingSubmissions) {
            throw ValidationException::withMessages([
                'screenshot' => 'You have reached the maximum number of pending submissions.',
            ]);
        }

        $this->validationService->validateFile($file, $game);

        [$width, $height] = getimagesize($file->getRealPath());
        $this->validationService->validateResolution($width, $height, $game);
        $hash = $this->validationService->validateHash($file, $game);

        $prepared = $this->widthDoubler->prepare($file->getRealPath(), $width, $game);

        try {
            // Add to the pending collection so no conversions are generated yet.
            // Conversions are triggered later if a reviewer approves the screenshot.
            $media = $game
                ->addMedia($prepared->filePath)
                ->usingFileName($hash . '.' . $prepared->extension($file->guessExtension()))
                ->preservingOriginal()
                ->withCustomProperties(['sha1' => $hash])
                ->toMediaCollection('screenshots-pending');
        } finally {
            $prepared->cleanup();
        }

        $prepared->finalize($media);

        return GameScreenshot::create([
            'game_id' => $game->id,
            'media_id' => $media->id,
            'width' => $prepared->width,
            'height' => $height,
            'type' => $type,
            'is_primary' => false,
            'status' => GameScreenshotStatus::Pending,
            'captured_by_user_id' => $user->id,
        ]);
    }
}
