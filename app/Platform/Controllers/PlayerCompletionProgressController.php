<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Platform\Enums\UnlockMode;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerCompletionProgressController extends Controller
{
    private int $pageSize = 100;

    public function __invoke(Request $request): View
    {
        if (!config('feature.beat')) {
            abort(404);
        }

        $targetUsername = $request->route()->parameters['user'];
        $validatedData = $request->validate([
            'page.number' => 'sometimes|integer|min:1',
            'filter.system' => 'sometimes|integer|between:0,99|not_in:101',
            'filter.status' => 'sometimes|string|min:2|max:30',
        ]);

        $currentPage = (int) ($validatedData['page']['number'] ?? 1);
        $targetSystemId = (int) ($validatedData['filter']['system'] ?? 0);
        $targetGameStatus = $validatedData['filter']['status'] ?? null;

        $me = Auth::user() ?? null;
        // TODO: Remove when denormalized data is ready.
        if (!config('feature.aggregate_queries') && !$me) {
            abort(401);
        }

        $foundTargetUser = User::where('User', 'like', $targetUsername)->first();
        if (!$this->getCanViewTargetUser($foundTargetUser, $me)) {
            abort(404);
        }

        $userGamesList = getUsersCompletedGamesAndMax($targetUsername);
        $userSiteAwards = getUsersSiteAwards($targetUsername);

        // Only show filters for console IDs the user has actually associated with.
        $allAvailableConsoleIds = $this->getAllAvailableConsoleIds(
            $userGamesList,
            $userSiteAwards
        );

        // If the user is only wanting to see results for a specific console,
        // filter out every game and award that doesn't have that console ID.
        if ($targetSystemId) {
            [$userGamesList, $userSiteAwards] = $this->useSystemId(
                $targetSystemId,
                $userGamesList,
                $userSiteAwards
            );
        }

        // Remove invalid consoles and add some awards metadata to each entity.
        $filteredAndJoinedGamesList = $this->filterAndJoinGames(
            $userGamesList,
            $userSiteAwards,
        );

        $primaryCountsMetrics = $this->buildPrimaryCountsMetrics($filteredAndJoinedGamesList);

        // The user may only be wanting to see a certain subset of games, such
        // as only mastered games, or only unawarded games. Perform this filtering now.
        if ($targetGameStatus) {
            $filteredAndJoinedGamesList = $this->useGameStatusFilter(
                $targetGameStatus,
                $filteredAndJoinedGamesList
            );
        }

        usort($filteredAndJoinedGamesList, function ($a, $b) {
            return strtotime($b['MostRecentWonDate']) - strtotime($a['MostRecentWonDate']);
        });

        $milestones = $this->buildMilestones($filteredAndJoinedGamesList);

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

        return view('platform.completion-progress-page', [
            'allAvailableConsoleIds' => $allAvailableConsoleIds,
            'completedGamesList' => $paginatedGamesList,
            'currentPage' => $currentPage,
            'isFiltering' => $targetGameStatus || $targetSystemId,
            'me' => $me,
            'milestones' => $milestones,
            'primaryCountsMetrics' => $primaryCountsMetrics,
            'selectedConsoleId' => $targetSystemId,
            'selectedStatus' => $targetGameStatus,
            'siteAwards' => $userSiteAwards,
            'totalInList' => $totalInList,
            'totalPages' => $totalPages,
            'user' => $foundTargetUser,
        ]);
    }

    private function buildPrimaryCountsMetrics(array $filteredAndJoinedGamesList): array
    {
        $metrics = [
            'numPlayed' => 0,
            'numUnfinished' => 0,
            'numBeatenSoftcore' => 0,
            'numBeatenHardcore' => 0,
            'numCompleted' => 0,
            'numMastered' => 0,
        ];

        $metrics['numPlayed'] = count($filteredAndJoinedGamesList);

        foreach ($filteredAndJoinedGamesList as $game) {
            if (!isset($game['HighestAwardKind'])) {
                $metrics['numUnfinished']++;
            } elseif ($game['HighestAwardKind'] === 'beaten-softcore') {
                $metrics['numBeatenSoftcore']++;
            } elseif ($game['HighestAwardKind'] === 'beaten-hardcore') {
                $metrics['numBeatenHardcore']++;
            } elseif ($game['HighestAwardKind'] === 'completed') {
                $metrics['numCompleted']++;
            } elseif ($game['HighestAwardKind'] === 'mastered') {
                $metrics['numMastered']++;
            }
        }

        return $metrics;
    }

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
            if ($me && $me->toArray()['Permissions'] >= Permissions::Moderator) {
                return true;
            }

            return false;
        }

        return true;
    }

    private function filterAndJoinGames(array $gamesList, array $siteAwards): array
    {
        /**
         * We need to append the most prestigious award kind+date to the game entities,
         * and we need to do it extremely fast. We'll [A] prepare some lookup tables,
         * then [B] iterate once while appending the entities with constant time O(1).
         */

        // [A] Prepare the lookup tables.
        $awardsLookup = [];
        $awardsDateLookup = [];
        $hasMasteryAwardLookup = [];

        foreach ($siteAwards as $award) {
            $key = $award['AwardData']; // Game ID

            if ($award['AwardType'] == AwardType::GameBeaten) {
                // Check if a higher-ranked award ('completed' or 'mastered') is already present.
                if (!isset($awardsLookup[$key]) || ($awardsLookup[$key] != 'completed' && $awardsLookup[$key] != 'mastered')) {
                    $awardsLookup[$key] =
                        $award['AwardDataExtra'] == UnlockMode::Softcore
                            ? 'beaten-softcore'
                            : 'beaten-hardcore';

                    $awardsDateLookup[$key] = $award['AwardedAt'];
                }
            } elseif ($award['AwardType'] == AwardType::Mastery) {
                $awardsLookup[$key] =
                    $award['AwardDataExtra'] == UnlockMode::Softcore
                        ? 'completed'
                        : 'mastered';

                $awardsDateLookup[$key] = $award['AwardedAt'];
                $hasMasteryAwardLookup[$key] = true;
            }
        }

        // [B] Iterate once while appending the entities with constant time O(1).
        $filteredAndJoined = [];
        foreach ($gamesList as &$game) {
            if ($game['ConsoleID'] !== 101 && isValidConsoleId($game['ConsoleID'])) {
                if (isset($awardsLookup[$game['GameID']])) {
                    $game['HighestAwardKind'] = $awardsLookup[$game['GameID']];
                    $game['HighestAwardDate'] = $awardsDateLookup[$game['GameID']];

                    // Check if the game has been beaten but not mastered.
                    if (
                        $game['HighestAwardKind'] != 'mastered'
                        && $game['HighestAwardKind'] != 'completed'
                        && !isset($hasMasteryAwardLookup[$game['GameID']])
                    ) {
                        $game['HasNoAssociatedMasteryAward'] = true;
                    }
                }

                $filteredAndJoined[] = $game;
            }
        }

        return $filteredAndJoined;
    }

    private function useGameStatusFilter(string $statusValue, array $filteredAndJoinedGamesList): array
    {
        $filters = [
            'unawarded' => fn ($game) => !isset($game['HighestAwardKind']),
            'awarded' => fn ($game) => isset($game['HighestAwardKind']),

            'pristine-mastered' => fn ($game) => isset($game['HighestAwardKind'])
                && ($game['HighestAwardKind'] === 'mastered' && $game['PctWonHC'] == 1),

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
        ];

        if (isset($filters[$statusValue])) {
            return array_filter($filteredAndJoinedGamesList, $filters[$statusValue]);
        }

        return $filteredAndJoinedGamesList;
    }

    private function useSystemId(int $targetSystemId, array $userGamesList, array $userSiteAwards): array
    {
        if (!isValidConsoleId($targetSystemId)) {
            return [$userGamesList, $userSiteAwards];
        }

        $filteredGamesList = array_filter($userGamesList, fn ($game) => $game['ConsoleID'] == $targetSystemId);
        $filteredSiteAwards = array_filter($userSiteAwards, fn ($award) => $award['ConsoleID'] == $targetSystemId);

        return [$filteredGamesList, $filteredSiteAwards];
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
