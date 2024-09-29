<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Data\GameListPagePropsData;
use App\Platform\Data\SystemData;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameListController extends Controller
{
    public function index(GameListRequest $request): InertiaResponse
    {
        /** @var ?User $user */
        $user = $request->user();

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::AllGames,
            user: $user,
            page: $request->getPage(),
            filters: $request->getFilters(),
            sort: $request->getSort(),
        );

        $filterableSystemOptions = System::active()
            ->get()
            ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
            ->values()
            ->all();

        $can = UserPermissionsData::fromUser($user)->include('develop');

        $props = new GameListPagePropsData(
            paginatedGameListEntries: $paginatedData,
            filterableSystemOptions: $filterableSystemOptions,
            can: $can,
        );

        return Inertia::render('game-list/index', $props);
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
