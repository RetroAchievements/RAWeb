<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Data\GameListPagePropsData;
use App\Platform\Data\SystemData;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use App\Platform\Requests\GameRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Jenssegers\Agent\Agent;

class GameController extends Controller
{
    protected function resourceName(): string
    {
        return 'game';
    }

    public function index(GameListRequest $request): InertiaResponse
    {
        /** @var ?User $user */
        $user = $request->user();

        $this->authorize('viewAny', [Game::class, $user]);

        $isMobile = (new Agent())->isMobile();

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::AllGames,
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

        $filterableSystemOptions = System::active()
            ->gameSystems()
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

    public function popular(): void
    {
        $this->authorize('viewAny', $this->resourceClass());

        // return view('game.popular');
    }

    public function create(): void
    {
        $this->authorize('store', $this->resourceClass());
    }

    public function store(Request $request): void
    {
        $this->authorize('store', $this->resourceClass());
    }

    public function show(Request $request, Game $game, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $game);

        if (!$this->resolvesToSlug($game->slug, $slug)) {
            return redirect($game->canonicalUrl);
        }

        /**
         * add user context
         */
        $playerGame = $request->user() ? $request->user()->games()->where('games.id', $game->id)->first() : null;

        $game = $playerGame ?? $game;

        $game->load([
            'achievements' => function ($query) use ($playerGame) {
                // $query->with('game');

                // $query->published();

                /*
                 * add user context
                 */
                if ($playerGame) {
                    $query->withUnlocksByUser(request()->user());
                    $query->orderByDesc('unlocked_hardcore_at');
                    $query->orderByDesc('unlocked_at');
                }
            },
            'leaderboards',
            'forumTopic',
        ]);

        // $game->achievements->each->setRelation('game', $game);

        return view($this->resourceName() . '.show')->with('game', $game);
    }

    public function edit(Game $game): View
    {
        $this->authorize('update', $game);

        return view($this->resourceName() . '.edit')->with('game', $game);
    }

    public function update(GameRequest $request, Game $game): RedirectResponse
    {
        $this->authorize('update', $game);

        $game->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('game', 'update'));
    }

    public function destroy(Game $game): void
    {
        $this->authorize('delete', $game);
    }

    public function random(GameListRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Game::class);

        $randomGame = (new GetRandomGameAction())->execute(
            GameListType::AllGames,
            filters: $request->getFilters(),
        );

        if (!$randomGame) {
            return redirect()->back()->with('error', 'No games with achievements found.');
        }

        return redirect()->route('game.show', ['game' => $randomGame]);
    }
}
