<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\AwardType;
use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Community\Models\Ticket;
use App\Community\Models\UserGameListEntry;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

class GameListService
{
    public bool $withLeaderboardCounts = true;
    public bool $withTicketCounts = false;

    /** @var ?Collection<int, System> */
    public ?Collection $consoles = null;
    public array $games = [];
    public ?array $userProgress = null;

    public function initializeUserProgress(?User $user, array $gameIds): void
    {
        $this->userProgress = null;
        if (!$user) {
            return;
        }

        $this->initializeUserGames($user, $gameIds);
        $this->initializeUserAwards($user, $gameIds);
    }

    public function initializeGameList(array $gameIds): void
    {
        if ($this->withTicketCounts) {
            $gameTicketsList = Ticket::whereIn('ReportState', [TicketState::Open, TicketState::Request])
                ->join('Achievements', 'Achievements.ID', '=', 'Ticket.AchievementID')
                ->whereIn('Achievements.GameID', $gameIds)
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

        $gameModels = Game::whereIn('ID', $gameIds)
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
            ->get()
            ->keyBy('ID');

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

            $game['ConsoleName'] = $this->consoles[$gameModel->ConsoleID]->Name;
            $game['SortTitle'] = $game['Title'];
            if (substr($game['Title'], 0, 1) == '~') {
                $tilde = strrpos($game['Title'], '~');
                $game['SortTitle'] = trim(substr($game['Title'], $tilde + 1) . ' ' . substr($game['Title'], 0, $tilde + 1));
            }

            $this->games[] = $game;
        }
    }

    public function filterGameList(callable $filterFunction, array $options = []): void
    {
        $countBefore = count($this->games);
        $this->games = array_filter($this->games, function ($game) use ($filterFunction, $options) {
            return $filterFunction($game, $options);
        });
        $countAfter = count($this->games);

        if ($countAfter < $countBefore) {
            $allConsoleIds = collect($this->games)->pluck('ConsoleID')->unique();

            $this->consoles = $this->consoles->filter(function ($console) use ($allConsoleIds) {
                return $allConsoleIds->contains($console->ID);
            });
        }
    }

    public function useGameStatusFilter(array $game, string $statusValue): bool
    {
        $foundProgress = $this->userProgress[$game['ID']] ?? null;

        $hasAwardKind = function ($kind) use ($foundProgress) {
            return isset($foundProgress['HighestAwardKind']) && $foundProgress['HighestAwardKind'] === $kind;
        };

        $isStatusEqual = function (?array $progress, string $status) {
            return ($progress['HighestAwardKind'] ?? null) === $status;
        };

        switch ($statusValue) {
            case 'unstarted':
                return !$foundProgress;

            case 'unawarded':
                return !isset($foundProgress['HighestAwardKind']);

            case 'awarded':
                return isset($foundProgress['HighestAwardKind']);

            case 'any-beaten':
                return $hasAwardKind('beaten-softcore') || $hasAwardKind('beaten-hardcore');

            case 'any-hardcore':
                return $foundProgress['completion_percentage_hardcore'] > 0;

            case 'any-softcore':
                return $foundProgress['completion_percentage'] !== $foundProgress['completion_percentage_hardcore'];

            case 'eq-beaten-softcore':
            case 'eq-beaten-hardcore':
            case 'eq-completed':
            case 'eq-mastered':
                return $isStatusEqual($foundProgress, mb_substr($statusValue, 3)); // remove "eq-"

            case 'eq-revised':
                return ($hasAwardKind('completed') || $hasAwardKind('mastered')) && $foundProgress['completion_percentage_hardcore'] < 1;

            case 'gte-beaten-softcore':
                return $hasAwardKind('beaten-softcore') || $hasAwardKind('completed');

            case 'gte-beaten-hardcore':
                return $hasAwardKind('beaten-hardcore') || $hasAwardKind('mastered');

            case 'gte-completed':
                return $hasAwardKind('completed') || $hasAwardKind('mastered');

            default:
                return true;
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

    private function initializeUserAwards(?User $user, array $gameIds): void
    {
        $userSiteAwards = getUsersSiteAwards($user->User);

        $awardsLookup = [];
        $awardsDateLookup = [];
        $hasMasteryAwardLookup = [];

        foreach ($userSiteAwards as $award) {
            $gameId = $award['AwardData'];
            if (!in_array($gameId, $gameIds)) {
                continue;
            }

            if ($award['AwardType'] == AwardType::GameBeaten) {
                // Check if a higher-ranked award ('completed' or 'mastered') is already present.
                if (!isset($awardsLookup[$gameId]) || ($awardsLookup[$gameId] != 'completed' && $awardsLookup[$gameId] != 'mastered')) {
                    $awardsLookup[$gameId] =
                        $award['AwardDataExtra'] == UnlockMode::Softcore
                            ? 'beaten-softcore'
                            : 'beaten-hardcore';

                    $awardsDateLookup[$gameId] = $award['AwardedAt'];
                }
            } elseif ($award['AwardType'] == AwardType::Mastery) {
                $awardsLookup[$gameId] =
                    $award['AwardDataExtra'] == UnlockMode::Softcore
                        ? 'completed'
                        : 'mastered';

                $awardsDateLookup[$gameId] = $award['AwardedAt'];
                $hasMasteryAwardLookup[$gameId] = true;
            }
        }

        foreach ($this->userProgress as $userGameId => &$userGame) {
            if (isset($awardsLookup[$userGameId])) {
                $userGame['HighestAwardKind'] = $awardsLookup[$userGameId];
                $userGame['HighestAwardDate'] = $awardsDateLookup[$userGameId];
            }
        }
    }

    private function initializeUserGames(User $user, array $gameIds): void
    {
        $this->userProgress = $user->playerGames()
            ->whereIn('game_id', $gameIds)
            ->get([
                'game_id',
                'achievements_unlocked',
                'achievements_unlocked_hardcore',
                'completion_percentage',
                'completion_percentage_hardcore',
            ])
            ->mapWithKeys(function ($row, $key) {
                return [$row['game_id'] => [
                    'achievements_unlocked' => $row['achievements_unlocked'],
                    'achievements_unlocked_hardcore' => $row['achievements_unlocked_hardcore'],
                    'completion_percentage' => $row['completion_percentage'],
                    'completion_percentage_hardcore' => $row['completion_percentage_hardcore'],
                ]];
            })
            ->toArray();
    }

    private function getTitleColumn(array $filterOptions): array
    {
        return [
            'header' => 'Title',
            'render' => function ($game) use ($filterOptions) {
                if (!$filterOptions['console']) {
                    echo '<td class="py-2">';
                    echo Blade::render('
                        <x-game.multiline-avatar
                            :gameId="$ID"
                            :gameTitle="$Title"
                            :gameImageIcon="$ImageIcon"
                            :consoleName="$ConsoleName"
                        />', $game);
                    echo '</td>';
                } else {
                    echo '<td>';
                    echo Blade::render('
                        <x-game.multiline-avatar
                            :gameId="$ID"
                            :gameTitle="$Title"
                            :gameImageIcon="$ImageIcon"
                        />', $game);
                    echo '</td>';
                }
            },
        ];
    }

    private function renderNumberOrBlank(array $game, string $field): void
    {
        if ($game[$field] == 0) {
            echo '<td></td>';
        } else {
            echo '<td class="text-right">';
            echo localized_number($game[$field]);
            echo '</td>';
        }
    }

    private function renderFloatOrBlank(array $game, string $field): void
    {
        if ($game[$field] == 0) {
            echo '<td></td>';
        } else {
            echo '<td class="text-right">';
            echo sprintf("%01.2f", $game[$field]);
            echo '</td>';
        }
    }

    private function getAchievementCountColumn(): array
    {
        return [
            'header' => 'Achievements',
            'width' => 12,
            'tooltip' => 'The number of achievements in the set',
            'align' => 'right',
            'render' => function ($game) {
                $this->renderNumberOrBlank($game, 'achievements_published');
            },
        ];
    }

    private function getAchievementPointsColumn(): array
    {
        return [
            'header' => 'Points',
            'width' => 10,
            'tooltip' => 'The number of points associated to achievements in the set',
            'align' => 'right',
            'render' => function ($game) {
                $this->renderNumberOrBlank($game, 'points_total');
            },
        ];
    }

    private function getRetroRatioColumn(): array
    {
        return [
            'header' => 'RetroRatio',
            'width' => 10,
            'tooltip' => 'An estimate of rarity for achievements in the set',
            'align' => 'right',
            'render' => function ($game) {
                $this->renderFloatOrBlank($game, 'RetroRatio');
            },
        ];
    }

    private function getLeaderboardCountColumn(): array
    {
        return [
            'header' => 'Leaderboards',
            'width' => 10,
            'tooltip' => 'The number of leaderboards in the set',
            'align' => 'right',
            'render' => function ($game) {
                $this->renderNumberOrBlank($game, 'leaderboards_count');
            },
        ];
    }

    private function getPlayerCountColumn(): array
    {
        return [
            'header' => 'Players',
            'width' => 8,
            'tooltip' => 'The number of users who have played the set',
            'align' => 'right',
            'render' => function ($game) {
                $this->renderNumberOrBlank($game, 'players_total');
            },
        ];
    }

    private function getTicketCountColumn(): array
    {
        return [
            'header' => 'Tickets',
            'width' => 8,
            'tooltip' => 'The number of open tickets for achievements in the set',
            'align' => 'right',
            'render' => function ($game) {
                if ($game['NumTickets'] == 0) {
                    echo '<td></td>';
                } else {
                    echo '<td class="text-right">';
                    echo '<a href="/ticketmanager.php?g=' . $game['ID'] . '">';
                    echo localized_number($game['NumTickets']);
                    echo '</a></td>';
                }
            },
        ];
    }

    private function getUserProgressColumn(): array
    {
        return [
            'header' => 'Progress',
            'width' => 8,
            'tooltip' => 'Indicates how close you are to completing or mastering a set',
            'align' => 'center',
            'render' => function ($game) {
                if ($game['achievements_published'] == 0) {
                    echo '<td></td>';
                } else {
                    $gameProgress = $this->userProgress[$game['ID']] ?? null;
                    $softcoreProgress = $gameProgress['achievements_unlocked'] ?? 0;
                    $hardcoreProgress = $gameProgress['achievements_unlocked_hardcore'] ?? 0;
                    $tooltip = "$softcoreProgress of {$game['achievements_published']} unlocked";

                    echo '<td class="text-center">';
                    echo Blade::render('
                        <x-hardcore-progress
                            :softcoreProgress="$softcoreProgress"
                            :hardcoreProgress="$hardcoreProgress"
                            :maxProgress="$maxProgress"
                            :tooltip="$tooltip"
                        />', [
                            'softcoreProgress' => $softcoreProgress,
                            'hardcoreProgress' => $hardcoreProgress,
                            'maxProgress' => $game['achievements_published'],
                            'tooltip' => $tooltip,
                        ]);
                    echo '</td>';
                }
            },
        ];
    }

    private function getBacklogColumn(): array
    {
        return [
            'header' => 'Backlog',
            'width' => 6,
            'tooltip' => 'Whether or not the game is on your want to play list',
            'align' => 'center',
            'javascript' => function () {
                echo Blade::render('
                    <x-game-list-item.backlog-button-javascript />
                ');
            },
            'render' => function ($game) {
                echo '<td class="text-center">';
                echo Blade::render('
                    <x-game-list-item.backlog-button
                        :gameId="$gameId"
                        :isOnBacklog="$isOnBacklog"
                    />', [
                        'gameId' => $game['ID'],
                        'isOnBacklog' => $game['WantToPlay'] ?? false,
                    ]);
                echo '</td>';
            },
        ];
    }

    public function getColumns(array $filterOptions): array
    {
        $columns = [];

        $columns['title'] = $this->getTitleColumn($filterOptions);
        $columns['achievements'] = $this->getAchievementCountColumn();
        $columns['points'] = $this->getAchievementPointsColumn();
        $columns['retroratio'] = $this->getRetroRatioColumn();

        if ($this->withLeaderboardCounts) {
            $columns['leaderboards'] = $this->getLeaderboardCountColumn();
        }

        $columns['players'] = $this->getPlayerCountColumn();

        if ($this->withTicketCounts) {
            $columns['tickets'] = $this->getTicketCountColumn();
        }

        if ($this->userProgress != null) {
            $columns['progress'] = $this->getUserProgressColumn();
            $columns['backlog'] = $this->getBacklogColumn();
        }

        return $columns;
    }
}
