<?php

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\System;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Illuminate\Http\JsonResponse;
use Jenssegers\Agent\Agent;

class SystemApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(): void
    {
    }

    public function games(GameListRequest $request, System $systemId): JsonResponse
    {
        $system = $systemId;

        $this->authorize('view', $system);

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::System,
            targetSystemId: $system->id,
            user: $request->user(),
            page: $request->getPage(),
            filters: $request->getFilters(targetSystemId: $system->id),
            sort: $request->getSort(),
            perPage: (new Agent())->isMobile() ? 100 : $request->getPageSize(),
        );

        return response()->json($paginatedData);
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

    public function random(GameListRequest $request, System $systemId): JsonResponse
    {
        $system = $systemId;

        $randomGame = (new GetRandomGameAction())->execute(
            GameListType::System,
            user: $request->user(),
            filters: $request->getFilters(targetSystemId: $system->id),
            targetSystemId: $system->id,
        );

        return response()->json(['gameId' => $randomGame->id]);
    }
}
