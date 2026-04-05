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

class GameScreenshotApiController extends Controller
{
    public function store(Request $request, Game $game): JsonResponse
    {
        $this->authorize('create', [GameScreenshot::class, $game]);

        $validated = $request->validate([
            'file' => ['required', 'file'],
            'type' => ['required', new Enum(ScreenshotType::class)],
        ]);

        $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
            game: $game,
            file: $validated['file'],
            type: ScreenshotType::from($validated['type']),
            user: $request->user(),
        );

        $screenshot->load('media');
        $data = GameScreenshotData::fromGameScreenshot($screenshot);

        return response()->json($data, 201);
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
