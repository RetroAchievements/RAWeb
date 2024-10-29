<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UserGameListPagePropsData;
use App\Community\Enums\UserGameListType;
use App\Community\Requests\UserGameListRequest;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Data\SystemData;
use App\Platform\Enums\GameListType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Jenssegers\Agent\Agent;

class UserGameListController extends Controller
{
    public function index(UserGameListRequest $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        $isMobile = (new Agent())->isMobile();

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::UserPlay,
            user: $user,
            filters: $request->getFilters(),
            sort: $request->getSort(),
            perPage: $isMobile ? 100 : 25,

            /**
             * Ignore page params on mobile.
             * They're _always_ desktop-generated. Desktop uses smaller
             * page sizes, so respecting these params is highly undesirable.
             */
            page: $isMobile ? 1 : $request->getPage(),
        );

        // Only allow filtering by systems the user has games on their list for.
        $filterableSystemIds = $user->gameListEntries(UserGameListType::Play)
            ->join('GameData', 'SetRequest.GameID', '=', 'GameData.ID')
            ->distinct()
            ->pluck('GameData.ConsoleID');
        $filterableSystemOptions = System::whereIn('ID', $filterableSystemIds)
            ->get()
            ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
            ->values()
            ->all();

        $can = UserPermissionsData::fromUser($user)->include('develop');

        $props = new UserGameListPagePropsData(
            paginatedGameListEntries: $paginatedData,
            filterableSystemOptions: $filterableSystemOptions,
            can: $can,
        );

        return Inertia::render('game-list/play', $props);
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(UserGameListEntry $userGameListEntry): void
    {
    }

    public function edit(UserGameListEntry $userGameListEntry): void
    {
    }

    public function update(Request $request, UserGameListEntry $userGameListEntry): void
    {
    }

    public function destroy(UserGameListEntry $userGameListEntry): void
    {
    }
}
