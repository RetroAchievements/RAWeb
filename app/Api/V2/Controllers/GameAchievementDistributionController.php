<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Api\V2\Actions\BuildGameAchievementDistributionHistogramAction;
use App\Api\V2\Data\GameAchievementDistributionData;
use App\Api\V2\Data\GameAchievementDistributionMetaData;
use App\Api\V2\Data\SelfLinkData;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\OpenApiSpec\Attributes\WithDescription;

class GameAchievementDistributionController
{
    #[WithDescription(
        GameAchievementDistributionData::class,
        'Show how many players hold each unlock count for a game',
        alsoDocument: ['404'],
    )]
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

        return response()->json(new GameAchievementDistributionData(
            links: new SelfLinkData(self: $request->fullUrl()),
            meta: GameAchievementDistributionMetaData::from($result),
        ));
    }
}
