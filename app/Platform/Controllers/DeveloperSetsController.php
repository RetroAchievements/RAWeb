<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Community\Models\Ticket;
use App\Http\Controller;
use App\Models\User;
use App\Platform\Services\GameListService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeveloperSetsController extends Controller
{
    public function __construct(
        protected GameListService $gameListService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $username = $request->route('user');
        $user = User::firstWhere('User', $username);
        if ($user === null) {
            abort(404);
        }

        $loggedInUser = $request->user();
        $this->gameListService->withTicketCounts = true;

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:console,title,achievements,points,leaderboards,players,tickets,progress,retroratio,-title,-achievements,-points,-leaderboards,-players,-tickets,-progress,-retroratio',
            'filter.console' => 'sometimes|in:true,false',
            'filter.sole' => 'sometimes|in:true,false',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'title';
        $filterOptions = [
            'console' => ($validatedData['filter']['console'] ?? 'true') !== 'false',
            'sole' => ($validatedData['filter']['sole'] ?? 'false') !== 'false',
        ];

        $gameAuthoredAchievementsList = $user->authoredAchievements()->published()
            ->select(['GameID',
                DB::raw('COUNT(Achievements.ID) AS NumAuthoredAchievements'),
                DB::raw('SUM(Achievements.Points) AS NumAuthoredPoints'),
            ])
            ->groupBy('GameID')
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [$row['GameID'] => [
                    'NumAuthoredAchievements' => (int) $row['NumAuthoredAchievements'],
                    'NumAuthoredPoints' => (int) $row['NumAuthoredPoints'],
                ]];
            })
            ->toArray();

        $gameAuthoredLeaderboardsList = $user->authoredLeaderboards()
            ->select(['GameID',
                DB::raw('COUNT(LeaderboardDef.ID) AS NumAuthoredLeaderboards'),
            ])
            ->groupBy('GameID')
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [$row['GameID'] => (int) $row['NumAuthoredLeaderboards']];
            })
            ->toArray();

        $gameIDs = array_keys($gameAuthoredAchievementsList) +
            array_keys($gameAuthoredLeaderboardsList);

        $gameAuthoredTicketsList = Ticket::whereIn('ReportState', [TicketState::Open, TicketState::Request])
            ->join('Achievements', 'Achievements.ID', '=', 'Ticket.AchievementID')
            ->whereIn('Achievements.GameID', $gameIDs)
            ->where('Achievements.Author', $user->User)
            ->select(['GameID',
                DB::raw('COUNT(Ticket.ID) AS NumAuthoredTickets'),
            ])
            ->groupBy('GameID')
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [$row['GameID'] => [
                    'NumAuthoredTickets' => (int) $row['NumAuthoredTickets'],
                ]];
            })
            ->toArray();

        $this->gameListService->initializeUserProgress($loggedInUser, $gameIDs);
        $this->gameListService->initializeGameList($gameIDs);

        foreach ($this->gameListService->games as &$game) {
            $gameAuthoredAchievements = $gameAuthoredAchievementsList[$game['ID']] ?? null;
            $game['NumAuthoredAchievements'] = $gameAuthoredAchievements['NumAuthoredAchievements'] ?? 0;
            $game['NumAuthoredPoints'] = $gameAuthoredAchievements['NumAuthoredPoints'] ?? 0;

            $game['NumAuthoredLeaderboards'] = $gameAuthoredLeaderboardsList[$game['ID']] ?? 0;

            $gameAuthoredTickets = $gameAuthoredTicketsList[$game['ID']] ?? null;
            $game['NumAuthoredTickets'] = $gameAuthoredTickets['NumAuthoredTickets'] ?? 0;
        }

        if ($filterOptions['sole']) {
            $this->gameListService->filterGameList(function ($game) {
                return $game['NumAuthoredAchievements'] == $game['achievements_published']
                        && $game['NumAuthoredLeaderboards'] == $game['leaderboards_count'];
            });
        }

        $this->gameListService->mergeWantToPlay($loggedInUser);

        $this->sortGameList($sortOrder);

        $availableSorts = $this->gameListService->getAvailableSorts();
        $availableCheckboxFilters = [
            'console' => 'Group by console',
            'sole' => 'Sole developer',
        ];

        $columns = $this->gameListService->getColumns($availableCheckboxFilters);

        $columns['title']['tally'] = function ($game) { return 1; };
        $columns['title']['render_tally'] = function ($value) { echo "<td><b>Total:</b> $value games</td>"; };

        $columns['achievements']['tooltip'] = "The number of achievements created by {$user->User} in the set";
        $columns['achievements']['render'] = function ($game) {
            $this->renderNumberOfNumber($game, 'NumAuthoredAchievements', 'achievements_published');
        };
        $columns['achievements']['tally'] = function ($game) { return $game['NumAuthoredAchievements']; };

        $columns['points']['tooltip'] = "The number of points associated to achievements created by {$user->User} in the set";
        $columns['points']['render'] = function ($game) {
            $this->renderNumberOfNumber($game, 'NumAuthoredPoints', 'points_total');
        };
        $columns['points']['tally'] = function ($game) { return $game['NumAuthoredPoints']; };

        $columns['leaderboards']['tooltip'] = "The number of leaderboards created by {$user->User} in the set";
        $columns['leaderboards']['render'] = function ($game) {
            $this->renderNumberOfNumber($game, 'NumAuthoredLeaderboards', 'leaderboards_count');
        };
        $columns['leaderboards']['tally'] = function ($game) { return $game['NumAuthoredLeaderboards']; };

        $columns['tickets']['tooltip'] = "The number of open tickets for achievements created by {$user->User} in the set";
        $columns['tickets']['render'] = function ($game) {
            $this->renderNumberOfNumber($game, 'NumAuthoredTickets', 'NumTickets');
        };
        $columns['tickets']['tally'] = function ($game) { return $game['NumAuthoredTickets']; };

        return view('components.developer.sets-page', [
            'user' => $user,
            'consoles' => $this->gameListService->consoles,
            'games' => $this->gameListService->games,
            'sortOrder' => $sortOrder,
            'availableSorts' => $availableSorts,
            'filterOptions' => $filterOptions,
            'availableCheckboxFilters' => $availableCheckboxFilters,
            'columns' => $columns,
        ]);
    }

    private function renderNumberOfNumber(array $game, string $field1, string $field2): void
    {
        if ($game[$field2] === 0) {
            echo '<td></td>';

            return;
        }

        echo '<td class="text-right">';
        if ($game[$field1] !== $game[$field2]) {
            echo localized_number($game[$field1]);
            echo ' of ';
        }
        echo localized_number($game[$field2]);
        echo '</td>';
    }

    protected function sortGameList(string $sortOrder): void
    {
        $reverse = substr($sortOrder, 0, 1) === '-';
        $sortMatch = $reverse ? substr($sortOrder, 1) : $sortOrder;

        $sortFunction = match ($sortMatch) {
            default => null,
            'achievements' => function ($a, $b) {
                return $a['NumAuthoredAchievements'] <=> $b['NumAuthoredAchievements'];
            },
            'points' => function ($a, $b) {
                return $a['NumAuthoredPoints'] <=> $b['NumAuthoredPoints'];
            },
            'leaderboards' => function ($a, $b) {
                return $a['NumAuthoredLeaderboards'] <=> $b['NumAuthoredLeaderboards'];
            },
            'tickets' => function ($a, $b) {
                return $a['NumAuthoredTickets'] <=> $b['NumAuthoredTickets'];
            },
        };

        // if we're not changing the sort definition, just use the base
        if ($sortFunction === null) {
            $this->gameListService->sortGameList($sortOrder);

            return;
        }

        usort($this->gameListService->games, $sortFunction);
        if ($reverse) {
            $this->gameListService->games = array_reverse($this->gameListService->games);
        }
    }
}
