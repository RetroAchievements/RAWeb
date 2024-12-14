<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\BuildGameSetRelatedHubsAction;
use App\Platform\Actions\BuildHubBreadcrumbsAction;
use App\Platform\Data\GameSetData;
use App\Platform\Data\HubPagePropsData;
use App\Platform\Data\SystemData;
use App\Platform\Enums\GameListType;
use App\Platform\Enums\GameSetType;
use App\Platform\Requests\GameListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Jenssegers\Agent\Agent;

// TODO mature hub needs to be age gated
// TODO on rename hub, make sure it's always wrapped in square brackets
// TODO play around on mobile

class HubController extends Controller
{
    public function index(): void
    {
    }

    public function store(): void
    {
    }

    public function create(): void
    {
    }

    public function show(GameListRequest $request, ?GameSet $gameSet): InertiaResponse|RedirectResponse
    {
        // On "/hubs", $gameSet will initially be null. We're trying to
        // go to the central hub - manually find it and set the value.
        if (!$gameSet->exists) {
            $gameSet = GameSet::centralHub()->firstOrFail();
        } else {
            // If the user navigates directly to the central hub ID,
            // we should redirect them to "/hubs" instead.
            $centralHub = GameSet::centralHub()->first();
            if ($centralHub && $gameSet->id === $centralHub->id) {
                return redirect()->route('hub.index');
            }
        }

        // Return a 404 if this game set isn't actually for a hub.
        if ($gameSet->type !== GameSetType::Hub) {
            abort(404);
        }

        $this->authorize('view', $gameSet);

        /** @var ?User $user */
        $user = $request->user();

        $isMobile = (new Agent())->isMobile();

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::Hub,
            targetId: $gameSet->id,
            user: $user,
            filters: $request->getFilters(defaultAchievementsPublishedFilter: 'either'),
            sort: $request->getSort(),
            perPage: $isMobile ? 100 : 25,

            /**
             * Ignore page params on mobile.
             * They're _always_ desktop-generated. Desktop uses smaller
             * page sizes, so respecting these params is highly undesirable.
             */
            page: $isMobile ? 1 : $request->getPage(),
        );

        // Only allow filtering by systems the hub has games linked for.
        $filterableSystemIds = $gameSet->games()->distinct()->pluck('GameData.ConsoleID');
        $filterableSystemOptions = System::whereIn('ID', $filterableSystemIds)
            ->get()
            ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
            ->values()
            ->all();

        $can = UserPermissionsData::fromUser($user)->include('develop', 'manageGameSets');

        $props = new HubPagePropsData(
            hub: GameSetData::from($gameSet)->include('title', 'badgeUrl', 'updatedAt'),
            relatedHubs: (new BuildGameSetRelatedHubsAction())->execute($gameSet),
            breadcrumbs: (new BuildHubBreadcrumbsAction())->execute($gameSet),
            paginatedGameListEntries: $paginatedData,
            filterableSystemOptions: $filterableSystemOptions,
            can: $can,
        );

        return Inertia::render('hub/[gameSet]', $props);
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }
}
