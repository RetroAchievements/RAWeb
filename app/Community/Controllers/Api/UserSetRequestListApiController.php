<?php

namespace App\Community\Controllers\Api;

use App\Actions\GetUserDeviceKindAction;
use App\Http\Controller;
use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Enums\GameListSortField;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Illuminate\Http\JsonResponse;

class UserSetRequestListApiController extends Controller
{
    public function index(GameListRequest $request): JsonResponse
    {
        return $this->buildResponse($request);
    }

    public function userRequests(User $user, GameListRequest $request): JsonResponse
    {
        return $this->buildResponse($request, $user);
    }

    public function random(GameListRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Game::class);

        $filters = $this->buildFilters($request);

        $randomGame = (new GetRandomGameAction())->execute(
            GameListType::SetRequests,
            user: $request->user(),
            filters: $filters,
        );

        return response()->json(['gameId' => $randomGame->id]);
    }

    private function buildResponse(GameListRequest $request, ?User $targetUser = null): JsonResponse
    {
        $this->authorize('viewAny', Game::class);

        $isMobile = (new GetUserDeviceKindAction())->execute() === 'mobile';
        $filters = $this->buildFilters($request);

        if ($targetUser) {
            $filters['user'] = [$targetUser->display_name];
        }

        $sortParams = $targetUser
            ? ['defaultSortField' => GameListSortField::Title, 'isDefaultSortAsc' => true]
            : ['defaultSortField' => GameListSortField::NumRequests, 'isDefaultSortAsc' => false];

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::SetRequests,
            filters: $filters,
            page: $request->getPage(),
            perPage: $isMobile ? 100 : $request->getPageSize(),
            sort: $request->getSort(...$sortParams),
            user: $request->user(),
        );

        return response()->json($paginatedData);
    }

    private function buildFilters(GameListRequest $request): array
    {
        $filters = $request->getFilters(defaultAchievementsPublishedFilter: 'none');

        if (!isset($filters['system'])) {
            $filters['system'] = ['supported'];
        }

        return $filters;
    }
}
