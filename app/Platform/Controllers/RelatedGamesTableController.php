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
            'filter.status' => 'sometimes|string|in:all,unstarted,lt-beaten-softcore,gte-beaten-softcore,gte-beaten-hardcore,gte-completed,eq-mastered,eq-beaten-softcore-or-beaten-hardcore,any-softcore,revised',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'title';
        $filterOptions = [
            'console' => ($validatedData['filter']['console'] ?? 'false') !== 'false',
            'populated' => ($validatedData['filter']['populated'] ?? 'false') !== 'false',
        ];
        if (isset($validatedData['filter']['status'])) {
            $filterOptions['status'] = $validatedData['filter']['status'];
        }

        $gameIDs = GameAlternative::where('gameID', $gameId)->pluck('gameIDAlt')->toArray()
                 + GameAlternative::where('gameIDAlt', $gameId)->pluck('gameID')->toArray();

        $this->gameListService->initializeUserProgress($loggedInUser, $gameIDs);
        $this->gameListService->initializeGameList($gameIDs);

        if ($filterOptions['populated']) {
            $this->gameListService->filterGameList(function ($game) {
                return $game['achievements_published'] > 0;
            });
        }

        if (isset($filterOptions['status'])) {
            $this->gameListService->filterGameList(function ($game) use ($filterOptions) {
                return $this->gameListService->useGameStatusFilter($game, $filterOptions['status']);
            });
        }

        $this->gameListService->mergeWantToPlay($loggedInUser);

        $this->gameListService->sortGameList($sortOrder);

        $availableSorts = $this->gameListService->getAvailableSorts();
        $availableCheckboxFilters = [
            'console' => 'Group by console',
            'populated' => 'Only with achievements',
        ];
        $availableSelectFilters = [
            [
                'kind' => 'status',
                'label' => 'Status',
                'options' => [
                    'all' => 'All games',
                    'unstarted' => 'No achievements earned',
                    'lt-beaten-softcore' => 'Has progress, but no award',
                    'gte-beaten-softcore' => 'Beaten (softcore) or greater',
                    'gte-beaten-hardcore' => 'Beaten or greater',
                    'gte-completed' => 'Completed or mastered',
                    'eq-mastered' => 'Mastered',
                    'eq-beaten-softcore-or-beaten-hardcore' => 'Beaten, but still missing achievements',
                    'any-softcore' => 'Has any softcore progress',
                    'revised' => 'Completed or mastered, but the set was revised',
                ],
            ],
        ];

        return view('platform.components.game.game-list', [
            'consoles' => $this->gameListService->consoles,
            'games' => $this->gameListService->games,
            'sortOrder' => $sortOrder,
            'availableSorts' => $availableSorts,
            'filterOptions' => $filterOptions,
            'availableCheckboxFilters' => $availableCheckboxFilters,
            'availableSelectFilters' => $availableSelectFilters,
            'columns' => $this->gameListService->getColumns($filterOptions),
            'noGamesMessage' => 'No related games.',
        ]);
    }
}
