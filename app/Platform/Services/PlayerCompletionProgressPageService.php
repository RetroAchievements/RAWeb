<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\Permissions;
use App\Models\System;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerCompletionProgressPageService
{
    private int $pageSize = 100;

    public function __construct(
        protected PlayerProgressionService $playerProgressionService
    ) {
    }

    public function buildViewData(Request $request, User $foundTargetUser): array
    {
        $targetUsername = $foundTargetUser->username;
        $validatedData = $request->validate([
            'page.number' => 'sometimes|integer|min:1',
            'filter.system' => 'sometimes|integer|between:0,102|not_in:100',
            'filter.status' => 'sometimes|string|min:2|max:30',
            'sort' => 'sometimes|string|in:unlock_date,pct_won,-unlock_date,-pct_won,game_title,-game_title',
        ]);

        $currentPage = (int) ($validatedData['page']['number'] ?? 1);
        $targetSystemId = (int) ($validatedData['filter']['system'] ?? 0);
        $targetGameStatus = $validatedData['filter']['status'] ?? null;
        $sortOrder = $validatedData['sort'] ?? 'unlock_date';

        $me = Auth::user() ?? null;

        if (!$this->getCanViewTargetUser($foundTargetUser, $me)) {
            abort(404);
        }

        $userGamesList = getUsersCompletedGamesAndMax($targetUsername);
        $userSiteAwards = getUsersSiteAwards($foundTargetUser);

        // Only show filters for console IDs the user has actually associated with.
        $allAvailableConsoleIds = $this->getAllAvailableConsoleIds(
            $userGamesList,
            $userSiteAwards
        );

        // If the user is only wanting to see results for a specific console,
        // filter out every game and award that doesn't have that console ID.
        if ($targetSystemId) {
            [$userGamesList, $userSiteAwards] = $this->playerProgressionService->useSystemId(
                $targetSystemId,
                $userGamesList,
                $userSiteAwards
            );
        }

        // Remove invalid consoles and add some awards metadata to each entity.
        $filteredAndJoinedGamesList = $this->playerProgressionService->filterAndJoinGames(
            $userGamesList,
            $userSiteAwards,
            $foundTargetUser->id,
            allowEvents: $targetSystemId === System::Events,
        );
        $primaryCountsMetrics = $this->playerProgressionService->buildPrimaryCountsMetrics($filteredAndJoinedGamesList);

        // The user may only be wanting to see a certain subset of games, such
        // as only mastered games, or only unawarded games. Perform this filtering now.
        if ($targetGameStatus) {
            $filteredAndJoinedGamesList = $this->useGameStatusFilter(
                $targetGameStatus,
                $filteredAndJoinedGamesList
            );
        }

        $milestones = $this->buildMilestones($filteredAndJoinedGamesList);

        $filteredAndJoinedGamesList = $this->useSortedList($filteredAndJoinedGamesList, $sortOrder);

        $totalInList = count($filteredAndJoinedGamesList);
        $totalPages = ceil($totalInList / $this->pageSize);

        // Ensure the user isn't trying to go out of bounds.
        if ($currentPage < 1 || $currentPage > $totalPages) {
            $currentPage = 1;
        }

        $paginatedGamesList = array_slice(
            $filteredAndJoinedGamesList,
            ($currentPage - 1) * $this->pageSize,
            $this->pageSize,
        );

        $isMe = $me?->id === $foundTargetUser->id;

        return [
            'allAvailableConsoleIds' => $allAvailableConsoleIds,
            'completedGamesList' => $paginatedGamesList,
            'currentPage' => $currentPage,
            'isFiltering' => $targetGameStatus || $targetSystemId,
            'isMe' => $isMe,
            'me' => $me,
            'milestones' => $milestones,
            'primaryCountsMetrics' => $primaryCountsMetrics,
            'selectedConsoleId' => $targetSystemId,
            'selectedSortOrder' => $sortOrder,
            'selectedStatus' => $targetGameStatus,
            'seo' => $this->buildPageSeoMeta($isMe, $foundTargetUser),
            'siteAwards' => $userSiteAwards,
            'targetUsername' => $targetUsername,
            'totalInList' => $totalInList,
            'totalPages' => $totalPages,
            'user' => $foundTargetUser,
        ];
    }

    private function buildPageSeoMeta(bool $isMe, User $targetUser): array
    {
        $pageTitle = '';

        if ($isMe) {
            $pageTitle = 'Your Completion Progress';
        } else {
            $pageTitle = "{$targetUser->display_name}'s Completion Progress";
        }

        $pageDescription = "View {$targetUser->display_name}'s game completion stats and milestones on RetroAchievements. Track their played, unfinished, and mastered games from various systems.";

        return [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
        ];
    }

    private function useSortedList(array $filteredAndJoinedGamesList, string $sortOrder): array
    {
        if ($sortOrder === 'unlock_date') {
            usort($filteredAndJoinedGamesList, function ($a, $b) {
                return strtotime($b['MostRecentWonDate'] ?? '') - strtotime($a['MostRecentWonDate'] ?? '');
            });
        }

        if ($sortOrder === '-unlock_date') {
            usort($filteredAndJoinedGamesList, function ($a, $b) {
                return strtotime($a['MostRecentWonDate'] ?? '') - strtotime($b['MostRecentWonDate'] ?? '');
            });
        }

        if ($sortOrder === 'game_title') {
            usort($filteredAndJoinedGamesList, function ($a, $b) {
                return strcmp($a['SortTitle'], $b['SortTitle']);
            });
        }

        if ($sortOrder === '-game_title') {
            usort($filteredAndJoinedGamesList, function ($a, $b) {
                return strcmp($b['SortTitle'], $a['SortTitle']);
            });
        }

        if ($sortOrder === 'pct_won' || $sortOrder === '-pct_won') {
            usort($filteredAndJoinedGamesList, function ($a, $b) {
                if ($a['PctWon'] == $b['PctWon']) {
                    return $b['MaxPossible'] - $a['MaxPossible'];
                }
                if ($a['PctWon'] === 1) {
                    return -1;
                }
                if ($b['PctWon'] === 1) {
                    return 1;
                }

                return $a['PctWon'] - $b['PctWon'];
            });

            if ($sortOrder === '-pct_won') {
                $filteredAndJoinedGamesList = array_reverse($filteredAndJoinedGamesList);
            }
        }

        return $filteredAndJoinedGamesList;
    }

    // TODO extract to a MilestonesService
    private function buildMilestones(array $filteredAndJoinedGamesList): array
    {
        $milestones = [];

        $landmarks = [
            1, 5, 10, 20, 30, 40, 50, 75, 100, 125, 150, 175,
            200, 250, 300, 400, 500, 600, 700, 800, 900, 1000,
        ];

        // Extract games by award kind.
        $extractGamesByKind = function ($kind) use ($filteredAndJoinedGamesList) {
            return array_filter($filteredAndJoinedGamesList, function ($game) use ($kind) {
                return isset($game['HighestAwardKind']) && $game['HighestAwardKind'] === $kind;
            });
        };

        // Past 1000, we want to increment landmarks by 250.
        $calculateMilestones = function ($games, $kind) use (&$milestones, $landmarks) {
            usort($games, function ($a, $b) {
                return $a['HighestAwardDate'] <=> $b['HighestAwardDate'];
            });

            $lastLandmark = end($landmarks);
            while ($lastLandmark < count($games)) {
                $lastLandmark += 250;
                $landmarks[] = $lastLandmark;
            }

            foreach ($landmarks as $landmark) {
                if (count($games) >= $landmark) {
                    $milestones[] = [
                        'kind' => $kind,
                        'which' => $landmark,
                        'when' => $games[$landmark - 1]['HighestAwardDate'],
                        'game' => $games[$landmark - 1],
                    ];
                }
            }
        };

        $milestoneAwardKinds = [
            'beaten-softcore' => 'beaten-softcore',
            'beaten-hardcore' => 'beaten-hardcore',
            'completed' => 'completed',
            'mastered' => 'mastered',
        ];
        foreach ($milestoneAwardKinds as $kind => $label) {
            $games = $extractGamesByKind($kind);
            $calculateMilestones($games, $label);
        }

        // Use this to make sure we don't insert any duplicate game IDs as part of the "most recent" milestones.
        $allMilestoneGameIds = array_map(function ($milestone) {
            return $milestone['game']['GameID'];
        }, $milestones);

        // Add the most recent awards to the milestones list.
        $recentAwardsKinds = ['mastered', 'completed', 'beaten-hardcore', 'beaten-softcore'];
        foreach ($recentAwardsKinds as $awardKind) {
            $games = $extractGamesByKind($awardKind);
            usort($games, function ($a, $b) {
                return $b['HighestAwardDate'] <=> $a['HighestAwardDate']; // Sort in descending order by date
            });

            // Before inserting the 'most recent' milestone, check if its game ID is already somewhere in the milestones list.
            if (!empty($games) && !in_array($games[0]['GameID'], $allMilestoneGameIds)) {
                $milestones[] = [
                    'kind' => $awardKind,
                    'which' => 'most recent',
                    'when' => $games[0]['HighestAwardDate'],
                    'game' => $games[0],
                ];
            }
        }

        usort($milestones, function ($a, $b) {
            return $b['when'] <=> $a['when'];
        });

        return $milestones;
    }

    // TODO use a policy -- this should rely on the Folio page middleware
    private function getCanViewTargetUser(?User $user, ?User $me): bool
    {
        if (!$user) {
            return false;
        }

        $targetUsername = $user->User;

        if (!isValidUsername($targetUsername)) {
            return false;
        }

        if ($user->toArray()['Permissions'] < Permissions::Unregistered) {
            if (isset($me) && $me->toArray()['Permissions'] >= Permissions::Moderator) {
                return true;
            }

            return false;
        }

        return true;
    }

    private function useGameStatusFilter(string $statusValue, array $filteredAndJoinedGamesList): array
    {
        $filters = [
            'unawarded' => fn ($game) => !isset($game['HighestAwardKind']),
            'awarded' => fn ($game) => isset($game['HighestAwardKind']),

            'any-beaten' => fn ($game) => isset($game['HighestAwardKind'])
                && ($game['HighestAwardKind'] === 'beaten-softcore' || $game['HighestAwardKind'] === 'beaten-hardcore'),

            'any-hardcore' => fn ($game) => $game['PctWonHC'] > 0,
            'any-softcore' => fn ($game) => $game['PctWon'] !== $game['PctWonHC'],

            'eq-beaten-softcore' => fn ($game) => isset($game['HighestAwardKind']) && $game['HighestAwardKind'] === 'beaten-softcore',
            'eq-beaten-hardcore' => fn ($game) => isset($game['HighestAwardKind']) && $game['HighestAwardKind'] === 'beaten-hardcore',
            'eq-completed' => fn ($game) => isset($game['HighestAwardKind']) && $game['HighestAwardKind'] === 'completed',
            'eq-mastered' => fn ($game) => isset($game['HighestAwardKind']) && $game['HighestAwardKind'] === 'mastered',

            'eq-revised' => fn ($game) => isset($game['HighestAwardKind'])
                && ($game['HighestAwardKind'] === 'completed' || $game['HighestAwardKind'] === 'mastered')
                && ($game['PctWon'] < 1),

            'gte-beaten-softcore' => fn ($game) => isset($game['HighestAwardKind'])
                && ($game['HighestAwardKind'] === 'beaten-softcore' || $game['HighestAwardKind'] === 'completed'),

            'gte-beaten-hardcore' => fn ($game) => isset($game['HighestAwardKind'])
                && ($game['HighestAwardKind'] === 'beaten-hardcore' || $game['HighestAwardKind'] === 'mastered'),

            'gte-completed' => fn ($game) => isset($game['HighestAwardKind'])
                && ($game['HighestAwardKind'] === 'completed' || $game['HighestAwardKind'] === 'mastered'),

            'missing-unlocks' => fn ($game) => $game['PctWon'] < 1,
        ];

        if (isset($filters[$statusValue])) {
            return array_filter($filteredAndJoinedGamesList, $filters[$statusValue]);
        }

        return $filteredAndJoinedGamesList;
    }

    private function getAllAvailableConsoleIds(array $userGamesList, array $userSiteAwards): array
    {
        $consoleIdsFromGames = collect($userGamesList)->pluck('ConsoleID')->unique()->toArray();
        $consoleIdsFromAwards = collect($userSiteAwards)->pluck('ConsoleID')->unique()->toArray();

        $allAvailableConsoleIds = array_unique(array_merge($consoleIdsFromGames, $consoleIdsFromAwards));
        $allAvailableConsoleIds = array_filter($allAvailableConsoleIds, fn ($value) => !is_null($value));

        return $allAvailableConsoleIds;
    }
}
