<?php

namespace App\Community\Controllers\Api;

use App\Actions\GetUserDeviceKindAction;
use App\Community\Requests\UserGameListRequest;
use App\Http\Controller;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Enums\GameListType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserGameListApiController extends Controller
{
    public function index(UserGameListRequest $request): JsonResponse
    {
        $isMobile = (new GetUserDeviceKindAction())->execute() === 'mobile';

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            user: $request->user(),
            page: $request->getPage(),
            filters: $request->getFilters(),
            sort: $request->getSort(),
            perPage: $isMobile ? 100 : $request->getPageSize(),
        );

        return response()->json($paginatedData);
    }

    public function store(Request $request, int $gameId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $userGameListEntry = UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameId,
            'type' => $request->input('userGameListType'),
        ]);

        return response()->json(['success' => true, 'data' => $userGameListEntry]);
    }

    public function show(UserGameListEntry $userGameListEntry): void
    {
    }

    public function update(Request $request, UserGameListEntry $userGameListEntry): void
    {
    }

    public function destroy(Request $request, int $gameId): JsonResponse
    {
        $user = $request->user();

        $type = $request->input('userGameListType');

        $userGameListEntry = UserGameListEntry::where('user_id', $user->id)
            ->where('GameID', $gameId)
            ->where('type', $type)
            ->first();

        if ($userGameListEntry) {
            $userGameListEntry->delete();
        }

        return response()->json(['success' => true]);
    }

    public function random(UserGameListRequest $request): JsonResponse
    {
        $randomGame = (new GetRandomGameAction())->execute(
            GameListType::UserPlay,
            user: $request->user(),
            filters: $request->getFilters(),
        );

        return response()->json(['gameId' => $randomGame->id]);
    }
}
