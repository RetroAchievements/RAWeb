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
        $showTickets = ($loggedInUser !== null && $loggedInUser->getPermissionsAttribute() >= Permissions::Developer);

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

        $userProgress = $this->gameListService->getUserProgress($loggedInUser, $gameIDs);
        [$games, $consoles] = $this->gameListService
            ->getGameList($gameIDs, $userProgress,
                          withLeaderboardCounts: true,
                          withTicketCounts: $showTickets);

        if ($filterOptions['populated']) {
            $this->gameListService->filterGameList($games, $consoles, function ($game) {
                return $game['achievements_published'] > 0;
            });
        }

        $this->gameListService->mergeWantToPlay($games, $loggedInUser);

        $this->gameListService->sortGameList($games, $sortOrder);

        return view('platform.components.game.related-games-table', [
            'consoles' => $consoles,
            'games' => $games,
            'sortOrder' => $sortOrder,
            'filterOptions' => $filterOptions,
            'userProgress' => $userProgress,
            'showTickets' => $showTickets,
        ]);
    }
}
