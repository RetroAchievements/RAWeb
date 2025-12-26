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
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserSetRequestListController extends Controller
{
    public function index(GameListRequest $request): InertiaResponse
    {
        $props = $this->buildPageProps($request);

        return Inertia::render('games/requests', $props);
    }

    public function userRequests(User $user, GameListRequest $request): InertiaResponse
    {
        $props = $this->buildPageProps($request, $user);

        return Inertia::render('games/requests/[user]', $props);
    }

    private function buildPageProps(GameListRequest $request, ?User $targetUser = null): GameListPagePropsData
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
            // For user-specific requests, default to all systems.
            // For general requests, default to supported systems.
            $filters['system'] = $targetUser ? ['all'] : ['supported'];
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

        // When viewing a specific user's requests, we need to override the unfilteredTotal
        // based on the current achievementsPublished filter setting.
        if ($targetUser && $userRequestInfo) {
            $currentAchievementsFilter = $filters['achievementsPublished'][0] ?? 'none';

            if ($currentAchievementsFilter === 'none') {
                // For "Active requests", unfilteredTotal is the count of active requests.
                $paginatedData->unfilteredTotal = $userRequestInfo->used;
            } else {
                // For "All requests", unfilteredTotal is the total count of all requests.
                $paginatedData->unfilteredTotal = UserGameListEntry::query()
                    ->where('user_id', $targetUser->id)
                    ->where('type', UserGameListType::AchievementSetRequest)
                    ->count();
            }
        }

        // If we're targeting a specific user, only show systems they've requested games for.
        if ($targetUser) {
            $systemIds = UserGameListEntry::query()
                ->where('user_id', $targetUser->id)
                ->where('type', UserGameListType::AchievementSetRequest)
                ->join('GameData', DB::raw('user_game_list_entries.game_id'), '=', 'GameData.ID')
                ->distinct()
                ->pluck(DB::raw('GameData.ConsoleID'));

            $filterableSystemOptions = System::query()
                ->gameSystems()
                ->whereIn('ID', $systemIds)
                ->get()
                ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
                ->values()
                ->all();
        } else {
            $filterableSystemOptions = System::query()
                ->gameSystems()
                ->get()
                ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
                ->values()
                ->all();
        }

        return new GameListPagePropsData(
            paginatedGameListEntries: $paginatedData,
            filterableSystemOptions: $filterableSystemOptions,
            can: UserPermissionsData::fromUser($currentUser),
            persistenceCookieName: $persistenceCookieName,
            persistedViewPreferences: $request->getCookiePreferences(),
            targetUser: $targetUser ? UserData::fromUser($targetUser)->include('displayName', 'id') : null,
            userRequestInfo: $userRequestInfo,
        );
    }
}
