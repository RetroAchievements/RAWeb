<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\BuildActivePlayersAction;
use App\Community\Requests\ActivePlayersRequest;
use App\Http\Controller;
use Illuminate\Http\JsonResponse;

class ActivePlayersApiController extends Controller
{
    public function index(ActivePlayersRequest $request): JsonResponse
    {
        $activePlayers = (new BuildActivePlayersAction())->execute(
            perPage: (int) $request->input('perPage', 300),
            page: (int) $request->input('page', 1),
            gameIds: $request->input('gameIds'),
            search: $request->input('search'),
        );

        return response()->json($activePlayers);
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
