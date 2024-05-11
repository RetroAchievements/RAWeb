<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\Permissions;
use App\Models\System;
use Illuminate\Http\Request;

class SystemGamesPageService
{
    public function __construct(
        protected GameListService $gameListService,
    ) {
    }

    public function buildViewData(Request $request, System $system): array
    {
        $loggedInUser = request()->user();
        $this->gameListService->withTicketCounts = (
            $loggedInUser !== null
            && $loggedInUser->getPermissionsAttribute() >= Permissions::Developer
        );
        $this->gameListService->withConsoleNames = false;

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:console,title,achievements,points,leaderboards,players,tickets,progress,retroratio,-title,-achievements,-points,-leaderboards,-players,-tickets,-progress,-retroratio',
            'filter.populated' => 'sometimes|string|in:yes,no,all',
            'filter.status' => 'sometimes|string|in:all,unstarted,lt-beaten-softcore,gte-beaten-softcore,gte-beaten-hardcore,gte-completed,eq-mastered,eq-beaten-softcore-or-beaten-hardcore,any-softcore,revised',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'title';
        $filterOptions = [
            'populated' => $validatedData['filter']['populated'] ?? 'yes',
        ];
        if (isset($validatedData['filter']['status'])) {
            $filterOptions['status'] = $validatedData['filter']['status'];
        }

        $gameIds = $system->games()->get(['ID'])->pluck('ID')->toArray();
        $totalUnfilteredCount = count($gameIds);

        $this->gameListService->initializeUserProgress($loggedInUser, $gameIds);
        $this->gameListService->initializeGameList($gameIds, $system->id === System::Events);

        $this->gameListService->filterGameList(function ($game) use ($filterOptions) {
            return $this->usePopulatedFilter($game, $filterOptions['populated']);
        });

        if (isset($filterOptions['status'])) {
            $this->gameListService->filterGameList(function ($game) use ($filterOptions) {
                return $this->gameListService->useGameStatusFilter($game, $filterOptions['status']);
            });
        }

        $this->gameListService->mergeWantToPlay($loggedInUser);
        $this->gameListService->sortGameList($sortOrder);

        $availableSorts = $this->gameListService->getAvailableSorts();
        $availableCheckboxFilters = [];
        $availableRadioFilters = [
            [
                'kind' => 'populated',
                'label' => 'Has achievements',
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No',
                    'all' => 'Either',
                ],
            ],
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

        $games = $this->gameListService->games;

        return [
            'availableCheckboxFilters' => $availableCheckboxFilters,
            'availableRadioFilters' => $availableRadioFilters,
            'availableSelectFilters' => $availableSelectFilters,
            'availableSorts' => $availableSorts,
            'columns' => $this->gameListService->getColumns(),
            'filterOptions' => $filterOptions,
            'gameListConsoles' => $this->gameListService->consoles,
            'games' => $games,
            'pageMetaDescription' => $this->buildPageMetaDescription($request, $system, $games),
            'shouldAlwaysShowMetaSurface' => !isValidConsoleId($system->id) || $system->id === System::Events,
            'sortOrder' => $sortOrder,
            'system' => $system,
            'totalUnfilteredCount' => $totalUnfilteredCount,
        ];
    }

    private function buildPageMetaDescription(Request $request, System $system, array $gameListGames): string
    {
        $pageMetaDescription = '';
        $areFiltersPristine = count($request->query()) === 0;

        if ($areFiltersPristine) {
            if (empty($gameListGames)) {
                $pageMetaDescription = "There are no games with achievements yet for {$system->name}. Check again soon.";
            } else {
                $numGames = count($gameListGames);
                if ($numGames < 100) {
                    $numGames = floor($numGames / 10) * 10; // round down to the nearest tenth
                } elseif ($numGames < 1000) {
                    $numGames = floor($numGames / 100) * 100; // round down to the nearest hundredth
                } else {
                    $numGames = floor($numGames / 1000) * 1000; // round down to the nearest thousandth
                }

                $localizedCount = localized_number($numGames);
                // WARNING: If you're tweaking this, try to make sure it doesn't exceed 200 characters.
                $pageMetaDescription = "Explore {$localizedCount}+ {$system->name} games on RetroAchievements. Our achievements bring a fresh perspective to classic games, letting you track your progress as you beat and master each title.";
            }
        }

        return $pageMetaDescription;
    }

    private function usePopulatedFilter(array $game, string $populatedValue): bool
    {
        switch ($populatedValue) {
            case 'yes':
                return $game['achievements_published'] > 0;

            case 'no':
                return !$game['achievements_published'];

            default:
                return true;
        }
    }
}
