<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Community\Models\Ticket;
use App\Community\Models\UserGameListEntry;
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

        $this->userProgress = $user->playerGames()
            ->whereIn('game_id', $gameIds)
            ->get(['game_id', 'achievements_unlocked', 'achievements_unlocked_hardcore'])
            ->mapWithKeys(function ($row, $key) {
                return [$row['game_id'] => [
                    'achievements_unlocked' => $row['achievements_unlocked'],
                    'achievements_unlocked_hardcore' => $row['achievements_unlocked_hardcore'],
                ]];
            })
            ->toArray();
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

    public function filterGameList(callable $filterFunction): void
    {
        $countBefore = count($this->games);
        $this->games = array_filter($this->games, $filterFunction);
        $countAfter = count($this->games);

        if ($countAfter < $countBefore) {
            $allConsoleIds = collect($this->games)->pluck('ConsoleID')->unique();

            $this->consoles = $this->consoles->filter(function ($console) use ($allConsoleIds) {
                return $allConsoleIds->contains($console->ID);
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

                    echo '<td>';
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
                $addButtonTooltip = __('user-game-list.play.add');
                $removeButtonTooltip = __('user-game-list.play.remove');

                echo <<<EOL
                function togglePlayListItem(id)
                {
                    $.post('/request/user-game-list/toggle.php', {
                        type: 'play',
                        game: id
                    })
                    .done(function () {
                        $("#add-to-list-" + id).toggle();
                        $("#remove-from-list-" + id).toggle();
                        if ($("#add-to-list-" + id).is(':visible')) {
                            $("#play-list-button-" + id).prop('title', '$addButtonTooltip');
                        } else {
                            $("#play-list-button-" + id).prop('title', '$removeButtonTooltip');
                        }
                    });
                }
                EOL;
            },
            'render' => function ($game) {
                $addVisibility = '';
                $removeVisibility = '';
                if ($game['WantToPlay'] ?? false) {
                    $addVisibility = ' class="hidden"';
                    $buttonTooltip = __('user-game-list.play.remove');
                } else {
                    $removeVisibility = ' class="hidden"';
                    $buttonTooltip = __('user-game-list.play.add');
                }

                echo '<td class="text-center">';
                echo '<button id="play-list-button-' . $game['ID'] . '" class="btn" type="button"';
                echo ' title="' . $buttonTooltip . '"';
                echo ' onClick="togglePlayListItem(' . $game['ID'] . ')">';
                echo '<div class="flex items-center gap-x-1">';
                echo '<div id="add-to-list-' . $game['ID'] . '"' . $addVisibility . '>';
                echo Blade::render('<x-fas-plus class="-mt-0.5 w-[12px] h-[12px]" />');
                echo '</div>';
                echo '<div id="remove-from-list-' . $game['ID'] . '"' . $removeVisibility . '>';
                echo Blade::render('<x-fas-check class="-mt-0.5 w-[12px] h-[12px]" />');
                echo '</div>';
                echo '</div>';
                echo '</button';
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
