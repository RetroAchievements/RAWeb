<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Actions\GetUserDeviceKindAction;
use App\Community\Enums\UserGameListType;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Data\GameListPagePropsData;
use App\Platform\Data\SystemData;
use App\Platform\Data\UserSetRequestInfoData;
use App\Platform\Enums\GameListSortField;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserSetRequestListController extends Controller
{
    public function index(GameListRequest $request): InertiaResponse
    {
        return $this->buildResponse($request);
    }

    public function userRequests(User $user, GameListRequest $request): InertiaResponse
    {
        return $this->buildResponse($request, $user);
    }

    private function buildResponse(GameListRequest $request, ?User $targetUser = null): InertiaResponse
    {
        /** @var ?User $currentUser */
        $currentUser = $request->user();

        $this->authorize('viewAny', [Game::class, $currentUser]);

        $isMobile = (new GetUserDeviceKindAction())->execute() === 'mobile';
        $persistenceCookieName = 'datatable_view_preference_setrequest_games';

        $request->setPersistenceCookieName($persistenceCookieName);
        $request->setDefaultPageSize(50);

        $filters = $request->getFilters(defaultAchievementsPublishedFilter: 'none');
        if (!isset($filters['system'])) {
            $filters['system'] = ['supported'];
        }

        $userRequestInfo = null;
        if ($targetUser) {
            $filters['user'] = [$targetUser->display_name];
            $userRequestInfo = UserSetRequestInfoData::fromArray(
                getUserRequestsInformation($targetUser)
            );
        }

        $sortParams = $targetUser
            ? ['defaultSortField' => GameListSortField::Title, 'isDefaultSortAsc' => true]
            : ['defaultSortField' => GameListSortField::NumRequests, 'isDefaultSortAsc' => false];

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::SetRequests,
            user: $currentUser,
            filters: $filters,
            sort: $request->getSort(...$sortParams),
            perPage: $isMobile ? 100 : $request->getPageSize(),
            page: $request->getPage(),
        );

        // If we're targeting a specific user, only show systems they've requested games for.
        if ($targetUser) {
            $systemIds = UserGameListEntry::where('user_id', $targetUser->id)
                ->where('type', UserGameListType::AchievementSetRequest)
                ->whereHas('game', function ($query) {
                    $query->where('achievements_published', 0);
                })
                ->join('GameData', 'SetRequest.GameID', '=', 'GameData.ID')
                ->distinct()
                ->pluck('GameData.ConsoleID');

            $filterableSystemOptions = System::gameSystems()
                ->whereIn('ID', $systemIds)
                ->get()
                ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
                ->values()
                ->all();
        } else {
            $filterableSystemOptions = System::gameSystems()
                ->get()
                ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
                ->values()
                ->all();
        }

        $props = new GameListPagePropsData(
            paginatedGameListEntries: $paginatedData,
            filterableSystemOptions: $filterableSystemOptions,
            can: UserPermissionsData::fromUser($currentUser),
            persistenceCookieName: $persistenceCookieName,
            persistedViewPreferences: $request->getCookiePreferences(),
            targetUser: $targetUser ? UserData::fromUser($targetUser)->include('displayName', 'id') : null,
            userRequestInfo: $userRequestInfo,
        );

        return Inertia::render('games/requests', $props);
    }
}
