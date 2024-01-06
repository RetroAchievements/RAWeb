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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GameListService
{
    public bool $withLeaderboardCounts = true;
    public bool $withTicketCounts = false;

    public ?array $userProgress = null;
    public ?Collection $consoles = null;
    public array $games = [];

    public function initializeUserProgress(?User $user, array $gameIDs): void
    {
        $this->userProgress = null;
        if (!$user) {
            return;
        }

        $this->userProgress = PlayerGame::where('user_id', $user->id)
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

    public function initializeGameList(array $gameIDs): void
    {
        if ($this->withTicketCounts) {
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

        if ($this->withLeaderboardCounts) {
            $gameModels = $gameModels->withCount('leaderboards');
        }

        $gameModels = $gameModels->get();

        $this->consoles = System::whereIn('ID', $gameModels->pluck('ConsoleID')->unique())
            ->orderBy('Name')
            ->get();

        $this->games = [];
        foreach ($gameModels as &$gameModel) {
            $game = $gameModel->toArray();

            if ($this->withLeaderboardCounts) {
                $game['leaderboards_count'] = $gameModel->leaderboards_count;
            }

            if ($this->withTicketCounts) {
                $gameTickets = $gameTicketsList[$gameModel->ID] ?? null;
                $game['NumTickets'] = $gameTickets['NumTickets'] ?? 0;
            }

            if ($this->userProgress !== null) {
                $gameProgress = $this->userProgress[$gameModel->ID]['achievements_unlocked_hardcore'] ?? 0;
                $game['CompletionPercentage'] = $gameModel->achievements_published ?
                    ($gameProgress * 100 / $gameModel->achievements_published) : 0;
            }

            $game['RetroRatio'] = $gameModel->points_total ? $gameModel->TotalTruePoints / $gameModel->points_total : 0.0;

            $game['ConsoleName'] = $this->consoles->firstWhere('ID', $game['ConsoleID'])->Name;
            $game['SortTitle'] = $game['Title'];
            if (substr($game['Title'], 0, 1) == '~') {
                $tilde = strrpos($game['Title'], '~');
                $game['SortTitle'] = trim(substr($game['Title'], $tilde + 1) . ' ' . substr($game['Title'], 0, $tilde + 1));
            }

            $this->games[] = $game;
        }
    }

    public function filterGameList(callable $filterFunction): void
    {
        $countBefore = count($this->games);
        $this->games = array_filter($this->games, $filterFunction);
        $countAfter = count($this->games);

        if ($countAfter < $countBefore) {
            $this->consoles = $this->consoles->filter(function ($console) {
                foreach ($this->games as $game) {
                    if ($game['ConsoleID'] == $console['ID']) {
                        return true;
                    }
                }

                return false;
            });
        }
    }

    public function mergeWantToPlay(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $wantToPlayGames = UserGameListEntry::where('user_id', $user->ID)
            ->where('type', UserGameListType::Play)
            ->pluck('GameID')
            ->toArray();

        foreach ($this->games as &$game) {
            $game['WantToPlay'] = in_array($game['ID'], $wantToPlayGames);
        }
    }

    public function getAvailableSorts(): array
    {
        $sorts = [
            'title' => 'Title',
            '-achievements' => 'Most achievements',
            '-points' => 'Most points',
            '-retroratio' => 'Highest RetroRatio',
            '-leaderboards' => 'Most leaderboards',
            '-players' => 'Most players',
            '-tickets' => 'Most tickets',
            '-progress' => 'Most progress',
        ];

        if (!$this->withLeaderboardCounts) {
            unset($sorts['-leaderboards']);
        }

        if (!$this->withTicketCounts) {
            unset($sorts['-tickets']);
        }

        if (!$this->userProgress) {
            unset($sorts['-progress']);
        }

        return $sorts;
    }

    public function sortGameList(string $sortOrder): void
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

        usort($this->games, $sortFunction);

        if ($reverse) {
            $this->games = array_reverse($this->games);
        }
    }
}
