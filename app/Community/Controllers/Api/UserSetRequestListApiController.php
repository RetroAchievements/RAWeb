<?php

namespace App\Community\Controllers\Api;

use App\Actions\GetUserDeviceKindAction;
use App\Community\Enums\UserGameListType;
use App\Http\Controller;
use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;
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

        // When viewing a specific user's requests, we need to override the unfilteredTotal
        // based on the current achievementsPublished filter setting.
        if ($targetUser) {
            $userRequestInfo = getUserRequestsInformation($targetUser);
            $currentAchievementsFilter = $filters['achievementsPublished'][0] ?? 'none';

            if ($currentAchievementsFilter === 'none') {
                // For "Active requests", unfilteredTotal is the count of active requests
                $paginatedData->unfilteredTotal = $userRequestInfo['used'];
            } else {
                // For "All requests", unfilteredTotal is the total count of all requests.
                $paginatedData->unfilteredTotal = UserGameListEntry::query()
                    ->where('user_id', $targetUser->id)
                    ->where('type', UserGameListType::AchievementSetRequest)
                    ->count();
            }
        }

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
