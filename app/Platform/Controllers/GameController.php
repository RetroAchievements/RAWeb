<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Game;
use App\Platform\Requests\GameRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    protected function resourceName(): string
    {
        return 'game';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        /*
         * TODO: if slug is empty or does not match -> redirect to correctly slugged url
         */

        return view('resource.index')
            ->with('resource', $this->resourceName());
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
}
