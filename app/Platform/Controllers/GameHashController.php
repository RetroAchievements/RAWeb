<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Platform\Actions\BuildGameHashesPagePropsAction;
use App\Platform\Actions\ResolveSubsetGameRedirectAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameHashController extends Controller
{
    public function __construct(
        private ResolveSubsetGameRedirectAction $resolveSubsetRedirectAction,
        private BuildGameHashesPagePropsAction $buildPagePropsAction,
    ) {
    }

    protected function resourceName(): string
    {
        return 'game-hash';
    }

    public function index(Request $request, Game $game): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', $this->resourceClass());

        $targetSetId = $request->query('set') ? (int) $request->query('set') : null;

        // Check if this is a subset game that should redirect to its backing game.
        // eg: "/game/24186/hashes" -> "/game/668/hashes?set=8659"
        if (!$targetSetId) {
            if ($redirect = $this->redirectIfSubsetGame($game)) {
                return $redirect;
            }
        }

        // Validate the set ID belongs to this game.
        $targetAchievementSet = $this->resolveTargetAchievementSet($game, $targetSetId);
        if ($targetSetId !== null && !$targetAchievementSet) {
            return redirect()->route('game.hashes.index', ['game' => $game]);
        }

        $props = $this->buildPagePropsAction->execute(
            $game,
            $request->user(),
            $targetAchievementSet,
        );

        return Inertia::render('game/[game]/hashes', $props);
    }

    public function show(GameHash $gameHash): void
    {
    }

    public function edit(GameHash $gameHash): void
    {
    }

    public function update(Request $request, GameHash $gameHash): void
    {
    }

    public function destroy(GameHash $gameHash): void
    {
    }

    private function redirectIfSubsetGame(Game $game): ?RedirectResponse
    {
        $redirectData = $this->resolveSubsetRedirectAction->execute($game);

        if (!$redirectData) {
            return null;
        }

        return redirect()->route('game.hashes.index', [
            'game' => $redirectData['backingGameId'],
            'set' => $redirectData['achievementSetId'],
        ]);
    }

    private function resolveTargetAchievementSet(Game $game, ?int $targetSetId): ?GameAchievementSet
    {
        if ($targetSetId === null) {
            return null;
        }

        return $game->gameAchievementSets()
            ->where('achievement_set_id', $targetSetId)
            ->with('achievementSet')
            ->first();
    }
}
