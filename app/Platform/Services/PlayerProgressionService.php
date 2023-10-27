<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;

class PlayerProgressionService
{
    public function buildPrimaryCountsMetrics(array $filteredAndJoinedGamesList, ?int $consoleId = null): array
    {
        if ($consoleId) {
            $filteredAndJoinedGamesList = array_filter(
                $filteredAndJoinedGamesList,
                fn ($game) => isset($game['ConsoleID']) && $game['ConsoleID'] == $consoleId
            );
        }

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

    public function filterAndJoinGames(array $gamesList, array $siteAwards): array
    {
        /**
         * We need to append the most prestigious award kind+date to the game entities,
         * and we need to do it extremely fast. We'll [A] prepare some lookup tables,
         * then [B] iterate once while appending the entities with constant time O(1).
         * We also need to [C] add rows for games with awards but no progress (this edge
         * case can happen when sets are completely demoted).
         */
        $processedGameIds = [];

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
            if ($game['ConsoleID'] != 101 && isValidConsoleId($game['ConsoleID'])) {
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
                $processedGameIds[$game['GameID']] = true;
            }
        }

        // [C] Add rows for games with awards but no progress.
        foreach ($awardsLookup as $gameId => $awardKind) {
            $alreadyProcessed = false;
            foreach ($gamesList as $game) {
                if ($game['GameID'] == $gameId) {
                    $alreadyProcessed = true;
                    break;
                }
            }

            if (!$alreadyProcessed && !isset($processedGameIds[$gameId])) {
                $searchResults = array_filter($siteAwards, fn ($award) => $award['AwardData'] == $gameId);

                $award = null;
                if (!empty($searchResults)) {
                    $award = current($searchResults);
                }

                if ($award && isValidConsoleId($award['ConsoleID']) && $award['ConsoleID'] != 101) {
                    $newGame = [
                        'GameID' => $gameId,
                        'ConsoleID' => $award['ConsoleID'],
                        'ConsoleName' => config('systems')[$award['ConsoleID']]['name'],
                        'Title' => $award['Title'],
                        'HighestAwardKind' => $awardKind,
                        'HighestAwardDate' => $awardsDateLookup[$gameId],
                        'MostRecentWonDate' => date('Y-m-d H:i:s', $award['AwardedAt']),
                        'ImageIcon' => $award['ImageIcon'],
                        'NumAwarded' => 0,
                        'MaxPossible' => 0,
                        'PctWonHC' => 0,
                        'PctWon' => 1,
                        'FirstWonDate' => null,
                    ];

                    $filteredAndJoined[] = $newGame;
                    $processedGameIds[$gameId] = true;
                }
            }
        }

        return $filteredAndJoined;
    }

    public function useSystemId(int $targetSystemId, array $userGamesList, array $userSiteAwards): array
    {
        if (!isValidConsoleId($targetSystemId)) {
            return [$userGamesList, $userSiteAwards];
        }

        $filteredGamesList = array_filter($userGamesList, fn ($game) => $game['ConsoleID'] == $targetSystemId);
        $filteredSiteAwards = array_filter($userSiteAwards, fn ($award) => $award['ConsoleID'] == $targetSystemId);

        return [$filteredGamesList, $filteredSiteAwards];
    }
}
