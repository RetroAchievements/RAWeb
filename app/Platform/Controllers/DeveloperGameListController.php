<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Models\Game;
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
            'sort' => 'sometimes|string|in:console,title,achievements,points,leaderboards,players,-title,-achievements,-points,-leaderboards,-players',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'console';

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

        $gameModels = Game::whereIn('ID', $gameIDs)
            ->orderBy('Title')
            ->select([
                'ID', 'Title', 'ImageIcon', 'ConsoleID',
                'achievements_published', 'points_total', 'players_total',
            ])
            ->withCount('leaderboards')
            ->get();

        $consoles = System::whereIn('ID', $gameModels->pluck('ConsoleID')->unique())
            ->orderBy('Name')
            ->get();

        $games = [];
        foreach ($gameModels as &$gameModel) {
            $game = $gameModel->toArray();

            $gameAuthoredAchievements = $gameAuthoredAchievementsList[$gameModel->ID] ?? null;
            $game['NumAuthoredAchievements'] = $gameAuthoredAchievements['NumAuthoredAchievements'] ?? 0;
            $game['NumAuthoredPoints'] = $gameAuthoredAchievements['NumAuthoredPoints'] ?? 0;

            $game['NumAuthoredLeaderboards'] = $gameAuthoredLeaderboardsList[$gameModel->ID] ?? 0;
            $game['leaderboards_count'] = $gameModel->leaderboards_count;

            $game['ConsoleName'] = $consoles->firstWhere('ID', $game['ConsoleID'])->Name;
            $game['SortTitle'] = $game['Title'];
            if (substr($game['Title'], 0, 1) == '~') {
                $tilde = strrpos($game['Title'], '~');
                $game['SortTitle'] = trim(substr($game['Title'], $tilde + 1) . ' ' . substr($game['Title'], 0, $tilde + 1));
            }
            $games[] = $game;
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
        };
        usort($games, $sortFunction);

        return view('community.components.developer.dev-games-page', [
            'user' => $user,
            'consoles' => $consoles,
            'games' => $games,
            'sortOrder' => $sortOrder,
        ]);
    }
}
