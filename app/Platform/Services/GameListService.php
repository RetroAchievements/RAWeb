<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Community\Models\Ticket;
use App\Community\Models\UserGameListEntry;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Support\Facades\DB;

class GameListService
{
    public function getUserProgress(User $user, array $gameIDs): ?array
    {
        $userProgress = null;
        if (!$user) {
            return null;
        }

        return PlayerGame::where('user_id', $user->id)
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

    public function getGameList(array $gameIDs,
        ?array $userProgress = null,
        bool $withLeaderboardCounts = false,
        bool $withTicketCounts = false): array
    {
        if ($withTicketCounts) {
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
        } else {
            $gameTicketsList = [];
        }

        $gameModels = Game::whereIn('ID', $gameIDs)
            ->whereNotIn('ConsoleID', System::getNonGameSystems())
            ->orderBy('Title')
            ->select([
                'ID', 'Title', 'ImageIcon', 'ConsoleID', 'players_total',
                'achievements_published', 'points_total', 'TotalTruePoints',
            ]);

        if ($withLeaderboardCounts) {
            $gameModels = $gameModels->withCount('leaderboards');
        }

        $gameModels = $gameModels->get();

        $consoles = System::whereIn('ID', $gameModels->pluck('ConsoleID')->unique())
            ->orderBy('Name')
            ->get();

        $games = [];
        foreach ($gameModels as &$gameModel) {
            $game = $gameModel->toArray();

            if ($withLeaderboardCounts) {
                $game['leaderboards_count'] = $gameModel->leaderboards_count;
            }

            if ($withTicketCounts) {
                $gameTickets = $gameTicketsList[$gameModel->ID] ?? null;
                $game['NumTickets'] = $gameTickets['NumTickets'] ?? 0;
            }

            if ($userProgress !== null) {
                $gameProgress = $userProgress[$gameModel->ID]['achievements_unlocked_hardcore'] ?? 0;
                $game['CompletionPercentage'] = $gameModel->achievements_published ?
                    ($gameProgress * 100 / $gameModel->achievements_published) : 0;
            }

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

    public function filterGameList(array &$games, array &$consoles, callable $filterFunction): void
    {
        $countBefore = count($games);
        $games = array_filter($games, $filterFunction);
        $countAfter = count($games);

        if ($countAfter < $countBefore) {
            $consoles = $consoles->filter(function ($console) use ($games) {
                foreach ($games as $game) {
                    if ($game['ConsoleID'] == $console['ID']) {
                        return true;
                    }
                }

                return false;
            });
        }
    }

    public function mergeWantToPlay(array &$games, ?User $user): void
    {
        if ($user === null) {
            return;
        }

        $wantToPlayGames = UserGameListEntry::where('user_id', $user->ID)
            ->where('type', UserGameListType::Play)
            ->pluck('GameID')
            ->toArray();

        foreach ($games as &$game) {
            $game['WantToPlay'] = in_array($game['ID'], $wantToPlayGames);
        }
    }

    public function sortGameList(array &$games, string $sortOrder): void
    {
        $reverse = substr($sortOrder, 0, 1) === '-';
        $sortMatch = $reverse ? substr($sortOrder, 1) : $sortOrder;

        $sortFunction = match ($sortMatch) {
            default => function ($a, $b) {
                return $a['SortTitle'] <=> $b['SortTitle'];
            },
            'achievements' => function ($a, $b) use ($reverse) {
                if ($a['achievements_published'] == $b['achievements_published']) {
                    // same number of achievements; apply secondary sort on sort title
                    return $reverse ? $b['SortTitle'] <=> $a['SortTitle'] : $a['SortTitle'] <=> $b['SortTitle'];
                }

                return $a['achievements_published'] <=> $b['achievements_published'];
            },
            'points' => function ($a, $b) use ($reverse) {
                if ($a['points_total'] == $b['points_total']) {
                    // same number of points; apply secondary sort on sort title
                    return $reverse ? $b['SortTitle'] <=> $a['SortTitle'] : $a['SortTitle'] <=> $b['SortTitle'];
                }

                return $a['points_total'] <=> $b['points_total'];
            },
            'retroratio' => function ($a, $b) {
                return $a['RetroRatio'] <=> $b['RetroRatio'];
            },
            'leaderboards' => function ($a, $b) use ($reverse) {
                if ($a['leaderboards_count'] == $b['leaderboards_count']) {
                    // same number of leaderboards; apply secondary sort on sort title
                    return $reverse ? $b['SortTitle'] <=> $a['SortTitle'] : $a['SortTitle'] <=> $b['SortTitle'];
                }

                return $a['leaderboards_count'] <=> $b['leaderboards_count'];
            },
            'players' => function ($a, $b) {
                return $a['players_total'] <=> $b['players_total'];
            },
            'tickets' => function ($a, $b) use ($reverse) {
                // when sorting by tickets, always push games without achievements to the bottom
                if ($a['achievements_published'] == 0) {
                    if ($b['achievements_published'] != 0) {
                        return $reverse ? -1 : 1;
                    }
                } elseif ($b['achievements_published'] == 0) {
                    return $reverse ? 1 : -1;
                }

                if ($a['NumTickets'] == $b['NumTickets']) {
                    // same number of tickets; apply secondary sort on sort title
                    return $reverse ? $b['SortTitle'] <=> $a['SortTitle'] : $a['SortTitle'] <=> $b['SortTitle'];
                }

                return $a['NumTickets'] <=> $b['NumTickets'];
            },
            'progress' => function ($a, $b) use ($reverse) {
                if ($a['CompletionPercentage'] == $b['CompletionPercentage']) {
                    // same completion progress; apply secondary sort on sort title, grouping sets with achievements first
                    if ($a['achievements_published'] == 0) {
                        if ($b['achievements_published'] != 0) {
                            return $reverse ? -1 : 1;
                        }
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
