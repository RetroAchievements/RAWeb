<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PlayerGameController extends Controller
{
    public function index(User $user, ?System $system = null): View
    {
        $this->authorize('viewAny', [PlayerGame::class, $user]);

        $games = $user->playerGames()
            ->with([
                'game' => function ($query) {
                    $query->with('system');
                },
                'achievements' => function ($query) {
                    $query->with('game');
                },
            ])
            ->paginate();

        return view('player.game.index')
            ->with('system', $system)
            ->with('grid', $games)
            ->with('user', $user);
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(User $user, Game $game): View
    {
        $playerGame = $user->playerGames()
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->authorize('view', [PlayerGame::class, $playerGame]);

        $playerGame->loadMissing([
            'achievements' => function ($query) use ($user) {
                $query->withUnlocksByUser($user);
                $query->orderByDesc('unlocked_hardcore_at');
                $query->orderByDesc('unlocked_at');
            },
        ]);
        $playerGame->setRelation('game', $game);
        $playerGame->achievements->each->setRelation('game', $game);

        return view('player.game.show')
            ->with('user', $user)
            ->with('playerGame', $playerGame)
            ->with('game', $playerGame->game);
    }

    public function edit(User $user, Game $game): void
    {
        $playerGame = $user->playerGames()
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->authorize('update', [PlayerGame::class, $playerGame]);
    }

    public function update(Request $request, User $user, Game $game): void
    {
        $playerGame = $user->playerGames()
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->authorize('update', [PlayerGame::class, $playerGame]);
    }

    public function destroy(User $user, Game $game): void
    {
        $playerGame = $user->playerGames()
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->authorize('delete', [PlayerGame::class, $playerGame]);

        /*
         * TODO: detach
         */
    }
}
