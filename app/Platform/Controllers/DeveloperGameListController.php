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

        $games = [];
        foreach ($gameModels as &$gameModel) {
            $game = $gameModel->toArray();

            $gameAuthoredAchievements = $gameAuthoredAchievementsList[$gameModel->ID] ?? null;
            $game['NumAuthoredAchievements'] = $gameAuthoredAchievements['NumAuthoredAchievements'] ?? 0;
            $game['NumAuthoredPoints'] = $gameAuthoredAchievements['NumAuthoredPoints'] ?? 0;

            $game['NumAuthoredLeaderboards'] = $gameAuthoredLeaderboardsList[$gameModel->ID] ?? 0;
            $game['leaderboards_count'] = $gameModel->leaderboards_count;

            $games[] = $game;
        }

        $consoles = System::whereIn('ID', $gameModels->pluck('ConsoleID')->unique())
            ->orderBy('Name')
            ->get();

        return view('community.components.developer.dev-games-page', [
            'user' => $user,
            'consoles' => $consoles,
            'games' => $games,
        ]);
    }
}
