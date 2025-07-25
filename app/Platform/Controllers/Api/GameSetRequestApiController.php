<?php

namespace App\Platform\Controllers\Api;

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Http\Controller;
use App\Models\Game;
use App\Models\User;
use App\Platform\Data\GameSetRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameSetRequestApiController extends Controller
{
    public function store(Request $request, Game $game): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Only allow set requests for games without achievements.
        if ($game->achievements_published > 0) {
            return response()->json(['error' => 'already_has_achievements'], 422);
        }

        // Check if the user already requested this game.
        if ($user->gameListEntries(UserGameListType::AchievementSetRequest)->where('GameID', $game->id)->exists()) {
            return response()->json(['error' => 'already_requested'], 422);
        }

        $result = (new AddGameToListAction())->execute($user, $game, UserGameListType::AchievementSetRequest);

        if (!$result) {
            return response()->json(['error' => 'request_limit_reached'], 422);
        }

        // Return the updated set request data.
        $userRequestInfo = getUserRequestsInformation($user, $game->id);
        $setRequestData = new GameSetRequestData(
            hasUserRequestedSet: true,
            totalRequests: getSetRequestCount($game->id),
            userRequestsRemaining: $userRequestInfo['remaining'],
        );

        return response()->json(['data' => $setRequestData]);
    }

    public function destroy(Request $request, Game $game): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Check if the user has already requested this game.
        if (!$user->gameListEntries(UserGameListType::AchievementSetRequest)->where('GameID', $game->id)->exists()) {
            return response()->json(['error' => 'not_requested'], 422);
        }

        $result = (new RemoveGameFromListAction())->execute($user, $game, UserGameListType::AchievementSetRequest);

        if (!$result) {
            return response()->json(['error' => 'remove_failed'], 422);
        }

        // Return the updated set request data.
        $userRequestInfo = getUserRequestsInformation($user, $game->id);
        $setRequestData = new GameSetRequestData(
            hasUserRequestedSet: false,
            totalRequests: getSetRequestCount($game->id),
            userRequestsRemaining: $userRequestInfo['remaining'],
        );

        return response()->json(['data' => $setRequestData]);
    }
}
