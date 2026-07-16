<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Api\V2\Actions\BuildGameAchievementDistributionHistogramAction;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

class GameAchievementDistributionController
{
    public function __invoke(
        Request $request,
        int $gameId,
        BuildGameAchievementDistributionHistogramAction $buildHistogram,
    ): JsonResponse {
        $game = Game::find($gameId);
        if (!$game) {
            throw JsonApiException::error([
                'status' => '404',
                'title' => 'Not Found',
                'detail' => "No game found with ID {$gameId}.",
            ]);
        }

        $result = $buildHistogram->execute($game, $request->user());

        return response()->json([
            'links' => ['self' => $request->fullUrl()],
            'meta' => $result,
        ]);
    }
}
