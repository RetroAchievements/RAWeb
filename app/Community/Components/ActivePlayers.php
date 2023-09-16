<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Services\ActivePlayersService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class ActivePlayers extends Component
{
    protected ActivePlayersService $activePlayersService;
    public ?string $initialSearch = null;

    public function __construct(ActivePlayersService $activePlayersService, Request $request)
    {
        // DI
        $this->activePlayersService = $activePlayersService;

        $this->initialSearch = $request->cookie('active_players_search');
    }

    public function render(): View
    {
        $loadedActivePlayers = $this->activePlayersService->loadActivePlayers(
            $this->initialSearch
        );

        return view('community.components.active-players.active-players', [
            'activePlayersCount' => $loadedActivePlayers['count'],
            'initialActivePlayers' => $loadedActivePlayers['records'],
            'initialSearch' => $this->initialSearch,
            'totalActivePlayers' => $loadedActivePlayers['total'],
            'trendingGames' => $loadedActivePlayers['trendingGames'],
        ]);
    }
}
