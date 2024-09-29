<?php

namespace App\Platform\Controllers\Api;

use App\Platform\Requests\GameListRequest;
use App\Http\Controller;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Enums\GameListType;
use Illuminate\Http\JsonResponse;

class GameListApiController extends Controller
{
    public function index(GameListRequest $request): JsonResponse
    {
        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::AllGames,
            user: $request->user(),
            page: $request->getPage(),
            filters: $request->getFilters(),
            sort: $request->getSort(),
        );

        return response()->json($paginatedData);
    }

    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }
}