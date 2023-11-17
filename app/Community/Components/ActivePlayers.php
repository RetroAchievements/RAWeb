<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Services\ActivePlayersService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class ActivePlayers extends Component
{
    public ?string $initialSearch = null;
    public ?array $targetGameIds = null;
    public string $variant = 'home'; // 'home' | 'focused'

    public function __construct(
        protected ActivePlayersService $activePlayersService,
        Request $request,
        ?array $targetGameIds = null,
        ?string $variant = 'home',
    ) {
        $this->initialSearch = $variant !== 'focused' ? $request->cookie('active_players_search') : null;

        $this->targetGameIds = $targetGameIds;
        $this->variant = $variant;
    }

    public function render(): View
    {
        $loadedActivePlayers = $this->activePlayersService->loadActivePlayers(
            $this->initialSearch,
            isset($this->targetGameIds) ? true : false,
            $this->targetGameIds,
        );

        return view('community.components.active-players.active-players', [
            'activePlayersCount' => $loadedActivePlayers['count'],
            'initialActivePlayers' => $loadedActivePlayers['records'],
            'initialSearch' => $this->variant === 'focused' ? null : $this->initialSearch,
            'totalActivePlayers' => $loadedActivePlayers['total'],
            'targetGameIds' => $this->targetGameIds,
            'trendingGames' => $loadedActivePlayers['trendingGames'],
            'variant' => $this->variant,
        ]);
    }
}
