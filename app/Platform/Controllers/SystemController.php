<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Models\System;
use App\Platform\Requests\SystemRequest;
use App\Platform\Services\GameListService;
use App\Site\Enums\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    protected function resourceName(): string
    {
        return 'system';
    }

    public function __construct(
        protected GameListService $gameListService,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(System $system, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $system);

        if (!$this->resolvesToSlug($system->slug, $slug)) {
            return redirect($system->canonicalUrl);
        }

        /** @var System $system */
        $system = $system->withCount(['games', 'achievements', 'emulators'])->find($system->id);
        $system->load([
            'emulators' => function ($query) {
                $query->orderBy('name');
            },
        ]);
        $games = $system->games()->orderBy('updated_at')->take(5)->get();

        return view('system.show')
            ->with('system', $system)
            ->with('games', $games);
    }

    public function edit(System $system): View
    {
        $this->authorize('update', $system);

        return view($this->resourceName() . '.edit')->with('system', $system);
    }

    public function update(SystemRequest $request, System $system): RedirectResponse
    {
        $this->authorize('update', $system);

        $system->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('system', 'update'));
    }

    public function destroy(System $system): void
    {
    }

    public function games(Request $request, System $system): View
    {
        $this->authorize('viewAny', $this->resourceClass());

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
        $this->gameListService->initializeGameList($gameIds);

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

        return view($this->resourceName() . '-games-page', [
            'availableCheckboxFilters' => $availableCheckboxFilters,
            'availableRadioFilters' => $availableRadioFilters,
            'availableSelectFilters' => $availableSelectFilters,
            'availableSorts' => $availableSorts,
            'columns' => $this->gameListService->getColumns(),
            'filterOptions' => $filterOptions,
            'gameListConsoles' => $this->gameListService->consoles,
            'games' => $this->gameListService->games,
            'sortOrder' => $sortOrder,
            'system' => $system,
            'totalUnfilteredCount' => $totalUnfilteredCount,
        ]);
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
