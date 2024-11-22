<?php

namespace App\Community\Controllers\Api;

use App\Community\Requests\UserGameListRequest;
use App\Http\Controller;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Enums\GameListType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class UserGameListApiController extends Controller
{
    public function index(UserGameListRequest $request): JsonResponse
    {
        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            user: $request->user(),
            page: $request->getPage(),
            filters: $request->getFilters(),
            sort: $request->getSort(),
            perPage: (new Agent())->isMobile() ? 100 : 25,
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
}
