<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Community\Models\Ticket;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeveloperSetsController extends GameListControllerBase
{
    public function __invoke(Request $request): View
    {
        $username = $request->route('user');
        $user = User::firstWhere('User', $username);
        if ($user === null) {
            abort(404);
        }

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
                    'NumAuthoredAchievements' => $row['NumAuthoredAchievements'],
                    'NumAuthoredPoints' => $row['NumAuthoredPoints'],
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
                return [$row['GameID'] => $row['NumAuthoredLeaderboards']];
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
                    'NumAuthoredTickets' => $row['NumAuthoredTickets'],
                ]];
            })
            ->toArray();

        $userProgress = $this->getUserProgress($gameIDs);
        [$games, $consoles] = $this->getGameList($gameIDs, $userProgress, true);

        foreach ($games as &$game) {
            $gameAuthoredAchievements = $gameAuthoredAchievementsList[$game['ID']] ?? null;
            $game['NumAuthoredAchievements'] = $gameAuthoredAchievements['NumAuthoredAchievements'] ?? 0;
            $game['NumAuthoredPoints'] = $gameAuthoredAchievements['NumAuthoredPoints'] ?? 0;

            $game['NumAuthoredLeaderboards'] = $gameAuthoredLeaderboardsList[$game['ID']] ?? 0;

            $gameAuthoredTickets = $gameAuthoredTicketsList[$game['ID']] ?? null;
            $game['NumAuthoredTickets'] = $gameAuthoredTickets['NumAuthoredTickets'] ?? 0;
        }

        if ($filterOptions['sole']) {
            $games = array_filter($games, function ($game) {
                return $game['NumAuthoredAchievements'] == $game['achievements_published']
                        && $game['NumAuthoredLeaderboards'] == $game['leaderboards_count'];
            });
        }

        $player = $request->user();
        if ($player !== null) {
            $this->mergeWantToPlay($games, $player);
        }

        $this->sortGameList($games, $sortOrder);

        return view('platform.components.developer.sets-page', [
            'user' => $user,
            'consoles' => $consoles,
            'games' => $games,
            'sortOrder' => $sortOrder,
            'filterOptions' => $filterOptions,
            'userProgress' => $userProgress,
        ]);
    }

    protected function sortGameList(array &$games, string $sortOrder): void
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
            GameListControllerBase::sortGameList($games, $sortOrder);

            return;
        }

        usort($games, $sortFunction);
        if ($reverse) {
            $games = array_reverse($games);
        }
    }
}
