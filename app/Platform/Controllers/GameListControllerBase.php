<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Community\Models\Ticket;
use App\Community\Models\UserGameListEntry;
use App\Http\Controller;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameListControllerBase extends Controller
{
    protected function getUserProgress(array $gameIDs): ?array
    {
        $userProgress = null;
        if (!request()->user()) {
            return null;
        }

        return PlayerGame::where('user_id', request()->user()->id)
            ->whereIn('game_id', $gameIDs)
            ->get(['game_id', 'achievements_unlocked', 'achievements_unlocked_hardcore'])
            ->mapWithKeys(function ($row, $key) {
                return [$row['game_id'] => [
                    'achievements_unlocked' => $row['achievements_unlocked'],
                    'achievements_unlocked_hardcore' => $row['achievements_unlocked_hardcore'],
                ]];
            })
            ->toArray();
    }

    protected function getGameList(array $gameIDs, ?array $userProgress): array
    {
        $gameTicketsList = Ticket::whereIn('ReportState', [TicketState::Open, TicketState::Request])
            ->join('Achievements', 'Achievements.ID', '=', 'Ticket.AchievementID')
            ->whereIn('Achievements.GameID', $gameIDs)
            ->select(['GameID',
                DB::raw('COUNT(Ticket.ID) AS NumTickets'),
            ])
            ->groupBy('GameID')
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [$row['GameID'] => [
                    'NumTickets' => $row['NumTickets'],
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

        $games = [];
        foreach ($gameModels as &$gameModel) {
            $game = $gameModel->toArray();

            $game['leaderboards_count'] = $gameModel->leaderboards_count;

            $gameTickets = $gameTicketsList[$gameModel->ID] ?? null;
            $game['NumTickets'] = $gameTickets['NumTickets'] ?? 0;

            $gameProgress = $userProgress[$gameModel->ID]['achievements_unlocked_hardcore'] ?? 0;
            $game['CompletionPercentage'] = $gameModel->achievements_published ?
                ($gameProgress * 100 / $gameModel->achievements_published) : 0;

            $game['RetroRatio'] = $gameModel->points_total ? $gameModel->TotalTruePoints / $gameModel->points_total : 0.0;

            $game['ConsoleName'] = $consoles->firstWhere('ID', $game['ConsoleID'])->Name;
            $game['SortTitle'] = $game['Title'];
            if (substr($game['Title'], 0, 1) == '~') {
                $tilde = strrpos($game['Title'], '~');
                $game['SortTitle'] = trim(substr($game['Title'], $tilde + 1) . ' ' . substr($game['Title'], 0, $tilde + 1));
            }
            $games[] = $game;
        }

        return [$games, $consoles];
    }

    protected function mergeWantToPlay(array &$games, User $user): void
    {
        $wantToPlayGames = UserGameListEntry::where('user_id', $user->ID)
            ->where('type', UserGameListType::Play)
            ->pluck('GameID')
            ->toArray();

        foreach ($games as &$game) {
            $game['WantToPlay'] = in_array($game['ID'], $wantToPlayGames);
        }
    }

    protected function sortGameList(array &$games, string $sortOrder): void 
    {
        $reverse = substr($sortOrder, 0, 1) === '-';
        $sortMatch = $reverse ? substr($sortOrder, 1) : $sortOrder;
        $sortFunction = match ($sortMatch) {
            default => function ($a, $b) {
                return $a['SortTitle'] <=> $b['SortTitle'];
            },
            'achievements' => function ($a, $b) {
                return $a['achievements_published'] <=> $b['achievements_published'];
            },
            'points' => function ($a, $b) {
                return $a['points_total'] <=> $b['points_total'];
            },
            'retroratio' => function ($a, $b) {
                return $a['RetroRatio'] <=> $b['RetroRatio'];
            },
            'leaderboards' => function ($a, $b) {
                return $a['leaderboards_count'] <=> $b['leaderboards_count'];
            },
            'players' => function ($a, $b) {
                return $a['players_total'] <=> $b['players_total'];
            },
            'tickets' => function ($a, $b) {
                return $a['NumTickets'] <=> $b['NumTickets'];
            },
            'progress' => function ($a, $b) use ($reverse) {
                if ($a['CompletionPercentage'] == $b['CompletionPercentage']) {
                    // same completion progress; apply secondary sort on sort title, grouping sets with achievements first
                    if ($a['achievements_published'] == 0) {
                        if ($b['achievements_published'] != 0)
                            return $reverse ? -1 : 1;
                    } elseif ($b['achievements_published'] == 0) {
                        return $reverse ? 1 : -1;
                    }
                    return $reverse ? $b['SortTitle'] <=> $a['SortTitle'] : $a['SortTitle'] <=> $b['SortTitle'];
                }

                return $a['CompletionPercentage'] <=> $b['CompletionPercentage'];
            },
        };
        usort($games, $sortFunction);
        if ($reverse) {
            $games = array_reverse($games);
        }
    }
}
