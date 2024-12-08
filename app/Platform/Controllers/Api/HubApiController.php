<?php

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\GameSet;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Illuminate\Http\JsonResponse;
use Jenssegers\Agent\Agent;

class HubApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }

    public function games(GameListRequest $request, GameSet $gameSet): JsonResponse
    {
        $this->authorize('view', $gameSet);

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::Hub,
            targetId: $gameSet->id,
            user: $request->user(),
            page: $request->getPage(),
            filters: $request->getFilters(defaultAchievementsPublishedFilter: 'either'),
            sort: $request->getSort(),
            perPage: (new Agent())->isMobile() ? 100 : $request->getPageSize(),
        );

        return response()->json($paginatedData);
    }

    public function randomGame(GameListRequest $request, GameSet $gameSet): JsonResponse
    {
        $randomGame = (new GetRandomGameAction())->execute(
            GameListType::Hub,
            user: $request->user(),
            filters: $request->getFilters(defaultAchievementsPublishedFilter: 'either'),
            targetId: $gameSet->id
        );

        return response()->json(['gameId' => $randomGame->id]);
    }
}
