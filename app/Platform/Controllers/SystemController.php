<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Data\SystemData;
use App\Platform\Data\SystemGameListPagePropsData;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use App\Platform\Requests\SystemRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Jenssegers\Agent\Agent;

class SystemController extends Controller
{
    protected function resourceName(): string
    {
        return 'system';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function games(GameListRequest $request, System $system): InertiaResponse
    {
        $this->authorize('view', $system);

        /** @var ?User $user */
        $user = $request->user();

        $persistenceCookieName = 'datatable_view_preference_system_games';
        $request->setPersistenceCookieName($persistenceCookieName);

        $isMobile = (new Agent())->isMobile();

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::System,
            targetId: $system->id,
            user: $user,
            filters: $request->getFilters(targetSystemId: $system->id),
            sort: $request->getSort(),
            perPage: 100,

            /**
             * Ignore page params on mobile.
             * They're _always_ desktop-generated. Desktop uses smaller
             * page sizes, so respecting these params is highly undesirable.
             */
            page: $isMobile ? 1 : $request->getPage(),
        );

        $can = UserPermissionsData::fromUser($user)->include('develop');

        $props = new SystemGameListPagePropsData(
            system: SystemData::from($system)->include('iconUrl'),
            paginatedGameListEntries: $paginatedData,
            can: $can,
            persistenceCookieName: $persistenceCookieName,
            persistedViewPreferences: $request->getCookiePreferences(),
        );

        return Inertia::render('system/games', $props);
    }

    public function show(System $system, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $system);

        if (!$this->resolvesToSlug($system->slug, $slug)) {
            return redirect($system->canonicalUrl);
        }

        /** @var System $system */
        $system = $system->withCount(['games', 'achievements', 'emulators'])->find($system->id);
        $system->load([
            'emulators' => function ($query) {
                $query->orderBy('name');
            },
        ]);
        $games = $system->games()->orderBy('updated_at')->take(5)->get();

        return view('system.show')
            ->with('system', $system)
            ->with('games', $games);
    }

    public function edit(System $system): View
    {
        $this->authorize('update', $system);

        return view($this->resourceName() . '.edit')->with('system', $system);
    }

    public function update(SystemRequest $request, System $system): RedirectResponse
    {
        $this->authorize('update', $system);

        $system->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('system', 'update'));
    }

    public function destroy(System $system): void
    {
    }
}
