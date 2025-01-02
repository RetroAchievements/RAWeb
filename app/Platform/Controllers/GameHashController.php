<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Game;
use App\Models\GameHash;
use App\Platform\Data\GameData;
use App\Platform\Data\GameHashData;
use App\Platform\Data\GameHashesPagePropsData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameHashController extends Controller
{
    protected function resourceName(): string
    {
        return 'game-hash';
    }

    public function index(Request $request, Game $game): InertiaResponse
    {
        $this->authorize('viewAny', $this->resourceClass());

        $gameData = GameData::fromGame($game)->include('badgeUrl', 'forumTopicId', 'system');
        $hashes = GameHashData::fromCollection($game->hashes);
        $can = UserPermissionsData::fromUser($request->user())->include('manageGameHashes');

        $props = new GameHashesPagePropsData($gameData, $hashes, $can);

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
