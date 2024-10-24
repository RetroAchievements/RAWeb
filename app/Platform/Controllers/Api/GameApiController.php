<?php

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\Game;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Illuminate\Http\JsonResponse;

class GameApiController extends Controller
{
    public function index(GameListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Game::class);

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::AllGames,
            user: $request->user(),
            page: $request->getPage(),
            filters: $request->getFilters(),
            sort: $request->getSort(),
        );

        return response()->json($paginatedData);
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
}
