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
        $this->gameListService->withTicketCounts = (
            $loggedInUser !== null
            && $loggedInUser->getPermissionsAttribute() >= Permissions::Developer
        );

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

        $gameIds = GameAlternative::where('gameID', $gameId)->pluck('gameIDAlt')->toArray()
                 + GameAlternative::where('gameIDAlt', $gameId)->pluck('gameID')->toArray();

        $this->gameListService->initializeUserProgress($loggedInUser, $gameIds);
        $this->gameListService->initializeGameList($gameIds);

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

        $availableSelectFilters = [];
        if ($loggedInUser) {
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
                        'eq-beaten-softcore-or-beaten-hardcore' => 'Beaten, but still missing achievements',
                        'gte-completed' => 'Completed or mastered',
                        'revised' => 'Completed or mastered, but the set was revised',
                        'eq-mastered' => 'Mastered',
                        'any-softcore' => 'Has any softcore progress',
                    ],
                ],
            ];
        }

        return view('platform.components.game.game-list', [
            'availableCheckboxFilters' => $availableCheckboxFilters,
            'availableSelectFilters' => $availableSelectFilters,
            'availableSorts' => $availableSorts,
            'columns' => $this->gameListService->getColumns($filterOptions),
            'consoles' => $this->gameListService->consoles,
            'filterOptions' => $filterOptions,
            'games' => $this->gameListService->games,
            'noGamesMessage' => 'No related games.',
            'sortOrder' => $sortOrder,
        ]);
    }
}
