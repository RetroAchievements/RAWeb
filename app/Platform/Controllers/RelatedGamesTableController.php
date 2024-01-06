<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Models\Game;
use App\Platform\Models\GameAlternative;
use App\Platform\Services\GameListService;
use App\Site\Enums\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RelatedGamesTableController extends Controller
{
    public function __construct(
        protected GameListService $gameListService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $gameId = $request->route('game');
        $game = Game::firstWhere('ID', $gameId);
        if ($game === null) {
            abort(404);
        }

        $loggedInUser = request()->user();
        $this->gameListService->withTicketCounts =
            ($loggedInUser !== null && $loggedInUser->getPermissionsAttribute() >= Permissions::Developer);

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:console,title,achievements,points,leaderboards,players,tickets,progress,retroratio,-title,-achievements,-points,-leaderboards,-players,-tickets,-progress,-retroratio',
            'filter.console' => 'sometimes|in:true,false',
            'filter.populated' => 'sometimes|in:true,false',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'title';
        $filterOptions = [
            'console' => ($validatedData['filter']['console'] ?? 'false') !== 'false',
            'populated' => ($validatedData['filter']['populated'] ?? 'false') !== 'false',
        ];

        $gameIDs = GameAlternative::where('gameID', $gameId)->pluck('gameIDAlt')->toArray()
                 + GameAlternative::where('gameIDAlt', $gameId)->pluck('gameID')->toArray();

        $this->gameListService->initializeUserProgress($loggedInUser, $gameIDs);
        $this->gameListService->initializeGameList($gameIDs);

        if ($filterOptions['populated']) {
            $this->gameListService->filterGameList(function ($game) {
                return $game['achievements_published'] > 0;
            });
        }

        $this->gameListService->mergeWantToPlay($loggedInUser);

        $this->gameListService->sortGameList($sortOrder);

        $availableSorts = $this->gameListService->getAvailableSorts();
        $availableFilters = [
            'console' => 'Group by console',
            'populated' => 'Only with achievements',
        ];

        return view('platform.components.game.related-games-table', [
            'consoles' => $this->gameListService->consoles,
            'games' => $this->gameListService->games,
            'sortOrder' => $sortOrder,
            'availableSorts' => $availableSorts,
            'filterOptions' => $filterOptions,
            'availableFilters' => $availableFilters,
            'userProgress' => $this->gameListService->userProgress,
            'showTickets' => $this->gameListService->withTicketCounts,
        ]);
    }
}
