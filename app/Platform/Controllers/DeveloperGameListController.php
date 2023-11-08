<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Community\Models\Ticket;
use App\Http\Controller;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeveloperGameListController extends Controller
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
            'sole' => 'sometimes|boolean',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'console';
        $soleDeveloper = $validatedData['sole'] ?? false;

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

        $gameTicketsList = Ticket::whereIn('ReportState', [TicketState::Open, TicketState::Request])
            ->join('Achievements', 'Achievements.ID', '=', 'Ticket.AchievementID')
            ->whereIn('Achievements.GameID', $gameIDs)
            ->select(['GameID',
                DB::raw('COUNT(Ticket.ID) AS NumTickets'),
                DB::raw("SUM(CASE WHEN Achievements.Author='$user->User' THEN 1 ELSE 0 END) AS NumAuthoredTickets"),
            ])
            ->groupBy('GameID')
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [$row['GameID'] => [
                    'NumTickets' => $row['NumTickets'],
                    'NumAuthoredTickets' => $row['NumAuthoredTickets'],
                ]];
            })
            ->toArray();

        $gameModels = Game::whereIn('ID', $gameIDs)
            ->orderBy('Title')
            ->select([
                'ID', 'Title', 'ImageIcon', 'ConsoleID', 'players_total',
                'achievements_published', 'points_total', 'TotalTruePoints',
            ])
            ->withCount('leaderboards')
            ->get();

        $consoles = System::whereIn('ID', $gameModels->pluck('ConsoleID')->unique())
            ->orderBy('Name')
            ->get();

        $userProgress = null;
        if (request()->user()) {
            $userProgress = PlayerGame::where('user_id', request()->user()->id)
                ->whereIn('game_id', $gameIDs)
                ->select(['game_id', 'achievements_unlocked', 'achievements_unlocked_hardcore'])
                ->get()
                ->mapWithKeys(function ($row, $key) {
                    return [$row['game_id'] => [
                        'achievements_unlocked' => $row['achievements_unlocked'],
                        'achievements_unlocked_hardcore' => $row['achievements_unlocked_hardcore'],
                    ]];
                })
                ->toArray();
        }

        $games = [];
        foreach ($gameModels as &$gameModel) {
            $game = $gameModel->toArray();

            $gameAuthoredAchievements = $gameAuthoredAchievementsList[$gameModel->ID] ?? null;
            $game['NumAuthoredAchievements'] = $gameAuthoredAchievements['NumAuthoredAchievements'] ?? 0;
            $game['NumAuthoredPoints'] = $gameAuthoredAchievements['NumAuthoredPoints'] ?? 0;

            $game['NumAuthoredLeaderboards'] = $gameAuthoredLeaderboardsList[$gameModel->ID] ?? 0;
            $game['leaderboards_count'] = $gameModel->leaderboards_count;

            $gameTickets = $gameTicketsList[$gameModel->ID] ?? null;
            $game['NumTickets'] = $gameTickets['NumTickets'] ?? 0;
            $game['NumAuthoredTickets'] = $gameTickets['NumAuthoredTickets'] ?? 0;

            $gameProgress = $userProgress[$gameModel->ID]['achievements_unlocked_hardcore'] ?? 0;
            $game['CompletionPercentage'] = $gameProgress * 100 / $gameModel->achievements_published;

            $game['RetroRatio'] = $gameModel->TotalTruePoints / $gameModel->points_total;

            $game['ConsoleName'] = $consoles->firstWhere('ID', $game['ConsoleID'])->Name;
            $game['SortTitle'] = $game['Title'];
            if (substr($game['Title'], 0, 1) == '~') {
                $tilde = strrpos($game['Title'], '~');
                $game['SortTitle'] = trim(substr($game['Title'], $tilde + 1) . ' ' . substr($game['Title'], 0, $tilde + 1));
            }
            $games[] = $game;
        }

        if ($soleDeveloper) {
            $games = array_filter($games, function ($game) {
                return $game['NumAuthoredAchievements'] == $game['achievements_published']
                        && $game['NumAuthoredLeaderboards'] == $game['leaderboards_count'];
            });
        }

        $sortFunction = match ($sortOrder) {
            default => function ($a, $b) {
                return $a['SortTitle'] <=> $b['SortTitle'];
            },
            '-title' => function ($a, $b) {
                return $b['SortTitle'] <=> $a['SortTitle'];
            },
            'achievements' => function ($a, $b) {
                return $a['NumAuthoredAchievements'] <=> $b['NumAuthoredAchievements'];
            },
            '-achievements' => function ($a, $b) {
                return $b['NumAuthoredAchievements'] <=> $a['NumAuthoredAchievements'];
            },
            'points' => function ($a, $b) {
                return $a['NumAuthoredPoints'] <=> $b['NumAuthoredPoints'];
            },
            '-points' => function ($a, $b) {
                return $b['NumAuthoredPoints'] <=> $a['NumAuthoredPoints'];
            },
            'retroratio' => function ($a, $b) {
                return $a['RetroRatio'] <=> $b['RetroRatio'];
            },
            '-retroratio' => function ($a, $b) {
                return $b['RetroRatio'] <=> $a['RetroRatio'];
            },
            'leaderboards' => function ($a, $b) {
                return $a['NumAuthoredLeaderboards'] <=> $b['NumAuthoredLeaderboards'];
            },
            '-leaderboards' => function ($a, $b) {
                return $b['NumAuthoredLeaderboards'] <=> $a['NumAuthoredLeaderboards'];
            },
            'players' => function ($a, $b) {
                return $a['players_total'] <=> $b['players_total'];
            },
            '-players' => function ($a, $b) {
                return $b['players_total'] <=> $a['players_total'];
            },
            'tickets' => function ($a, $b) {
                return $a['NumTickets'] <=> $b['NumTickets'];
            },
            '-tickets' => function ($a, $b) {
                return $b['NumTickets'] <=> $a['NumTickets'];
            },
            'progress' => function ($a, $b) {
                return $a['CompletionPercentage'] <=> $b['CompletionPercentage'];
            },
            '-progress' => function ($a, $b) {
                return $b['CompletionPercentage'] <=> $a['CompletionPercentage'];
            },
        };
        usort($games, $sortFunction);

        return view('community.components.developer.dev-sets-page', [
            'user' => $user,
            'consoles' => $consoles,
            'games' => $games,
            'sortOrder' => $sortOrder,
            'soleDeveloper' => $soleDeveloper,
            'userProgress' => $userProgress,
        ]);
    }
}
