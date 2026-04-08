<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Platform\Actions\SubmitPendingGameScreenshotAction;
use App\Platform\Data\GameScreenshotData;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class GameScreenshotApiController extends Controller
{
    private const VALIDATION_ERROR_CODES = [
        'already been uploaded' => 'duplicate_hash',
        'dimensions' => 'invalid_resolution',
        'pending submissions' => 'pending_cap_reached',
    ];

    public function store(Request $request, Game $game): JsonResponse
    {
        $this->authorize('create', [GameScreenshot::class, $game]);

        $validated = $request->validate([
            'file' => ['required', 'file'],
            'type' => ['required', new Enum(ScreenshotType::class)],
        ]);

        try {
            $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
                game: $game,
                file: $validated['file'],
                type: ScreenshotType::from($validated['type']),
                user: $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $this->mapValidationErrorCode($e),
            ], 422);
        }

        $screenshot->load('media');
        $data = GameScreenshotData::fromGameScreenshot($screenshot);

        return response()->json($data, 201);
    }

    private function mapValidationErrorCode(ValidationException $e): string
    {
        $message = collect($e->errors())->flatten()->first() ?? '';

        foreach (self::VALIDATION_ERROR_CODES as $needle => $code) {
            if (str_contains($message, $needle)) {
                return $code;
            }
        }

        return 'validation_error';
    }

    public function destroy(Game $game, GameScreenshot $gameScreenshot): JsonResponse
    {
        $this->authorize('delete', $gameScreenshot);

        // Hard-delete the media file and the screenshot record.
        if ($gameScreenshot->media) {
            $gameScreenshot->media->delete();
        }

        $gameScreenshot->delete();

        return response()->json(null, 204);
    }
}
