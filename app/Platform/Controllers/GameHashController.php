<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Enums\GameHashCompatibility;
use App\Http\Controller;
use App\Models\Game;
use App\Models\GameHash;
use App\Platform\Actions\ResolveHashesForAchievementSetAction;
use App\Platform\Data\GameData;
use App\Platform\Data\GameHashData;
use App\Platform\Data\GameHashesPagePropsData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameHashController extends Controller
{
    protected function resourceName(): string
    {
        return 'game-hash';
    }

    public function index(Request $request, Game $game): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', $this->resourceClass());

        $targetSetId = $request->query('set') ? (int) $request->query('set') : null;

        // Validate the set ID belongs to this game.
        $targetAchievementSet = null;
        if ($targetSetId !== null) {
            $targetAchievementSet = $game->gameAchievementSets()
                ->where('achievement_set_id', $targetSetId)
                ->first();

            if (!$targetAchievementSet) {
                // Invalid set ID - redirect without param.
                return redirect()->route('game.hashes.index', ['game' => $game]);
            }
        }

        $game->load('hashes');

        $gameData = GameData::fromGame($game)->include('badgeUrl', 'forumTopicId', 'system');

        // Get filtered hashes based on the target set.
        $filteredHashes = (new ResolveHashesForAchievementSetAction())->execute($game, $targetAchievementSet);
        $hashes = GameHashData::fromCollection($filteredHashes);

        $incompatibleHashes = GameHashData::fromCollection($game->hashes->where('compatibility', GameHashCompatibility::Incompatible));
        $untestedHashes = GameHashData::fromCollection($game->hashes->where('compatibility', GameHashCompatibility::Untested));
        $patchRequiredHashes = GameHashData::fromCollection($game->hashes->where('compatibility', GameHashCompatibility::PatchRequired));
        $can = UserPermissionsData::fromUser($request->user())->include('manageGameHashes');

        $props = new GameHashesPagePropsData(
            game: $gameData,
            hashes: $hashes,
            incompatibleHashes: $incompatibleHashes,
            untestedHashes: $untestedHashes,
            patchRequiredHashes: $patchRequiredHashes,
            can: $can,
        );

        return Inertia::render('game/[game]/hashes', $props);
    }

    public function show(GameHash $gameHash): void
    {
        dump($gameHash->toArray());
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
}
